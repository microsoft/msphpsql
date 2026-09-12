"""Offline regression tests for PHP version and Windows build selection."""

import contextlib
import io
import os
import runpy
import unittest
from unittest import mock

import builddrivers
import buildtools


class PhpVersionTests(unittest.TestCase):
    def test_shared_validator_accepts_supported_formats(self):
        self.assertIs(builddrivers.validate_php_version, buildtools.validate_php_version)
        versions = [
            '7.0', '7.4.27', '8.3', '8.4.0', '8.5.0', '8.6', '8.6.0',
            '8.10.0', '10.0.0', '7.2-RC1', '7.2.RC1', '8.6.0-dev',
            '8.6.0.dev',
        ]
        for suffix in ('alpha1', 'beta3', 'RC1'):
            versions.extend('8.6.0' + separator + suffix for separator in ('', '-', '.'))
        for version in versions:
            with self.subTest(version=version):
                self.assertTrue(builddrivers.validate_php_version(version))
                builder = buildtools.BuildUtil(version, 'all', 'x64', 'nts', False)
                self.assertTrue(builder._validate_php_version(version))
                self.assertEqual(builder.phpver, version)

    def test_shared_validator_rejects_invalid_or_old_versions(self):
        versions = [
            None, '', '6.4.0', '6.4.0RC1', '8', '8.x', '8.6.0.1',
            'php-8.6.0RC1', '8.6.0RC', '8.6.0beta', '8.6.0alpha',
            '8.6.0rc1', '8.6.0--RC1', '8.6.0..RC1', '8.6.0-RC1junk',
            '8.6.0\n', ' 8.6.0', '8.6.0 ', '8.6.0&echo',
        ]
        for version in versions:
            with self.subTest(version=version):
                self.assertFalse(builddrivers.validate_php_version(version))
                with self.assertRaises(ValueError):
                    buildtools.BuildUtil(version, 'all', 'x64', 'nts', False)

    def test_cli_accepts_prereleases_and_rejects_invalid_versions(self):
        for version in ('8.6.0alpha1', '8.6.0beta3', '8.6.0RC1',
                        '8.6.0-beta3', '8.6.0.RC1', '8.6',
                        '6.4.0', '8.6.0\n'):
            with self.subTest(version=version), contextlib.ExitStack() as stack:
                util = stack.enter_context(mock.patch.object(buildtools, 'BuildUtil'))
                stack.enter_context(mock.patch.object(buildtools.os, 'chdir'))
                stack.enter_context(contextlib.redirect_stdout(io.StringIO()))
                stack.enter_context(mock.patch('sys.argv', [
                    builddrivers.__file__, '--PHPVER=' + version,
                    '--ARCH=x64', '--THREAD=nts', '--TESTING',
                ]))
                if buildtools.validate_php_version(version):
                    runpy.run_path(builddrivers.__file__, run_name='__main__')
                    util.assert_called_once_with(version, 'all', 'x64', 'nts', False, False)
                    util.return_value.build_drivers.assert_called_once()
                else:
                    with self.assertRaises(SystemExit) as error:
                        runpy.run_path(builddrivers.__file__, run_name='__main__')
                    self.assertEqual(error.exception.code, 1)
                    util.assert_not_called()


class WindowsBuildSelectionTests(unittest.TestCase):
    def test_compiler_and_output_paths(self):
        cases = (
            ('7.0', 'vs16'), ('7.4.27', 'vs16'), ('8.2.0', 'vs16'),
            ('8.3.99', 'vs16'), ('8.4.0', 'vs17'), ('8.5.0RC1', 'vs17'),
            ('8.6', 'vs18'), ('8.6.0alpha1', 'vs18'), ('8.10.0', 'vs18'),
            ('10.0.0', 'vs18'),
        )
        for version, compiler in cases:
            for arch, thread, debug in (('x64', 'nts', False), ('x86', 'ts', True)):
                with self.subTest(version=version, arch=arch):
                    builder = buildtools.BuildUtil(version, 'all', arch, thread, False, debug)
                    with contextlib.redirect_stdout(io.StringIO()):
                        self.assertEqual(builder.compiler_version('sdk'), compiler)
                        self.assertEqual(builder.compiler_version('sdk'), compiler)
                    source = os.path.join('sdk', 'php-sdk', 'phpdev', compiler, arch,
                                          'php-' + version + '-src')
                    self.assertEqual(builder.phpsrc_root('sdk'), source)
                    output = (os.path.join(source, 'x64', 'Release') if arch == 'x64'
                              else os.path.join(source, 'Debug_TS'))
                    self.assertEqual(builder.build_abs_path('sdk'), output)
                    label = ''.join(version.split('.')[:2])
                    self.assertEqual(builder.driver_new_name('sqlsrv', '.dll'),
                                     'php_sqlsrv_' + label + '_' + thread + '.dll')

    def test_build_generates_correct_tag_and_invokes_matching_sdk_launcher(self):
        cases = [
            ('7.4.27', 'php-7.4.27', 'vs16'),
            ('8.3.0', 'php-8.3.0', 'vs16'),
            ('8.4.0', 'php-8.4.0', 'vs17'),
            ('8.5.0', 'php-8.5.0', 'vs17'),
            ('8.6', 'php-8.6', 'vs18'),
            ('8.6.0', 'php-8.6.0', 'vs18'),
            ('8.10.0', 'php-8.10.0', 'vs18'),
            ('10.0.0', 'php-10.0.0', 'vs18'),
            ('8.6.0-dev', 'php-8.6.0dev', 'vs18'),
        ]
        for suffix in ('alpha1', 'beta3', 'RC1'):
            cases.extend(('8.6.0' + separator + suffix, 'php-8.6.0' + suffix, 'vs18')
                         for separator in ('', '-', '.'))
        for version, tag, compiler in cases:
            for arch, thread, debug in (('x64', 'nts', False), ('x86', 'ts', True)):
                with self.subTest(version=version, arch=arch), contextlib.ExitStack() as stack:
                    driver = builddrivers.BuildDriver(
                        version, 'all', arch, thread, debug, None, None, None, None, True, False)
                    builder = driver.util
                    work_dir = os.path.dirname(os.path.realpath(buildtools.__file__))
                    sdk = os.path.join(work_dir, 'php-sdk')
                    source = os.path.join(work_dir, 'Source')
                    # Generate the real batch text, but perform no filesystem or build operations.
                    batch = stack.enter_context(mock.patch('builtins.open', mock.mock_open()))
                    stack.enter_context(contextlib.redirect_stdout(io.StringIO()))
                    stack.enter_context(mock.patch.object(buildtools.platform, 'system', return_value='Windows'))
                    stack.enter_context(mock.patch.object(buildtools.os.path, 'exists',
                                                          side_effect=lambda path: path in (source, sdk)))
                    stack.enter_context(mock.patch.object(buildtools.os, 'chdir'))
                    move = stack.enter_context(mock.patch.object(buildtools.shutil, 'move'))
                    run = stack.enter_context(mock.patch.object(buildtools.subprocess, 'run'))
                    update = stack.enter_context(mock.patch.object(builder, 'update_driver_source'))
                    rename = stack.enter_context(mock.patch.object(builder, 'rename_binaries'))
                    copy = stack.enter_context(mock.patch.object(
                        builder, 'copy_binaries',
                        side_effect=lambda sdk_dir, copy_to_ext: builder.build_abs_path(sdk_dir)))

                    output = builder.build_drivers(make_clean=True, log_file='build.log')

                    text = ''.join(call.args[0] for call in batch().write.call_args_list)
                    folder = 'php-' + version + '-src'
                    self.assertIn('git clone -b ' + tag + ' --depth 1 --single-branch ', text)
                    self.assertIn('CD "' + folder + '"', text)
                    self.assertIn('--enable-sqlsrv=shared --enable-pdo --with-pdo-sqlsrv=shared', text)
                    self.assertEqual('--disable-zts' in text, thread == 'nts')
                    self.assertEqual('--enable-debug' in text, debug)
                    self.assertIn('nmake clean', text)
                    self.assertEqual(run.call_args_list, [
                        mock.call(['git', 'pull'], check=True, capture_output=True, text=True),
                        mock.call(['phpsdk-' + compiler + '-' + arch + '.bat',
                                   '-t', 'phpsdk-build-task.bat'],
                                  check=True, shell=True, cwd=sdk),
                    ])
                    expected_source = os.path.join(sdk, 'phpdev', compiler, arch, folder)
                    expected_output = (os.path.join(expected_source, 'x64', 'Release')
                                       if arch == 'x64' else os.path.join(expected_source, 'Debug_TS'))
                    self.assertEqual(output, expected_output)
                    self.assertEqual(update.call_args_list, [
                        mock.call(source, 'sqlsrv'), mock.call(source, 'pdo_sqlsrv')])
                    move.assert_any_call(os.path.join(work_dir, 'phpsdk-build-task.bat'), sdk)
                    rename.assert_called_once_with(work_dir)
                    copy.assert_called_once_with(work_dir, False)


if __name__ == '__main__':
    unittest.main()
