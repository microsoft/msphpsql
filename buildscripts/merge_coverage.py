#!/usr/bin/env python3
"""Merge cross-platform Cobertura XML coverage reports into a single report.

Reads coverage XML files from Linux (gcovr) and Windows (OpenCppCoverage),
normalizes file paths to match the repo layout, deduplicates shared source
files, and writes a merged Cobertura XML.

Usage:
    python merge_coverage.py <workspace> <output_dir>
    python merge_coverage.py  # falls back to PIPELINE_WORKSPACE / BUILD_SOURCESDIRECTORY env vars
"""

import argparse
import xml.etree.ElementTree as ET
import os
import sys
import re
import glob
import shutil


def normalize_path(filename):
    """Normalize paths so shared files from different drivers merge together,
    and prefix with source/ to match the repo layout.

    Windows: D:\\...\\ext\\sqlsrv\\conn.cpp -> source/sqlsrv/conn.cpp
    Windows: D:\\...\\ext\\sqlsrv\\shared\\core_conn.cpp -> source/shared/core_conn.cpp
    Linux:   pdo_sqlsrv/shared/core_conn.cpp -> source/shared/core_conn.cpp
    Linux:   sqlsrv/shared/core_conn.cpp -> source/shared/core_conn.cpp
    """
    f = filename.replace('\\', '/')
    # Handle Windows absolute PDB paths
    m = re.search(r'/ext/((?:pdo_)?sqlsrv)/(.*)', f)
    if m:
        ext_name = m.group(1)
        rest = m.group(2)
        if rest.startswith('shared/'):
            return 'source/' + rest
        return 'source/' + ext_name + '/' + rest
    # Handle Linux relative paths: pdo_sqlsrv/shared/X or sqlsrv/shared/X -> source/shared/X
    m = re.match(r'(?:pdo_)?sqlsrv/(shared/.*)', f)
    if m:
        return 'source/' + m.group(1)
    # Already has a driver prefix like sqlsrv/ or pdo_sqlsrv/
    if re.match(r'(?:pdo_)?sqlsrv/', f):
        return 'source/' + f
    return f


def collect_coverage_files(workspace):
    """Find all coverage XML files from Linux and Windows artifacts."""
    coverage_files = []

    # Linux: single coverage.xml
    linux_xml = os.path.join(workspace, 'coverage-linux', 'coverage.xml')
    if os.path.exists(linux_xml):
        coverage_files.append(('linux', linux_xml))
        print(f'Found Linux coverage: {linux_xml}')

    # Windows: separate per-driver XMLs (coverage-sqlsrv.xml, coverage-pdo_sqlsrv.xml)
    win_dir = os.path.join(workspace, 'coverage-windows')
    if os.path.isdir(win_dir):
        for f in sorted(glob.glob(os.path.join(win_dir, 'coverage*.xml'))):
            coverage_files.append(('windows', f))
            print(f'Found Windows coverage: {f}')

    return coverage_files


def merge_coverage(coverage_files):
    """Parse and merge coverage data from multiple XML files."""
    coverage_data = {}
    file_sources = {}

    for platform, xml_path in coverage_files:
        input_label = f'{platform}:{os.path.basename(xml_path)}'
        print(f'Processing: {xml_path}')
        tree = ET.parse(xml_path)
        for pkg in tree.getroot().iter('package'):
            for cls in pkg.iter('class'):
                fname = cls.get('filename', '')
                fname = normalize_path(fname)
                if fname not in coverage_data:
                    coverage_data[fname] = {}
                    file_sources[fname] = set()
                file_sources[fname].add(input_label)
                for line in cls.iter('line'):
                    num = line.get('number')
                    hits = int(line.get('hits', '0'))
                    coverage_data[fname][num] = coverage_data[fname].get(num, 0) + hits

    return coverage_data, file_sources


def write_cobertura_xml(coverage_data, output_path):
    """Write merged coverage data as a Cobertura XML file."""
    root = ET.Element('coverage')
    root.set('version', '1')
    root.set('timestamp', '0')
    sources = ET.SubElement(root, 'sources')
    source = ET.SubElement(sources, 'source')
    source.text = '.'
    packages = ET.SubElement(root, 'packages')

    total_lines = 0
    covered_lines = 0

    pkg_data = {}
    for fname, lines in sorted(coverage_data.items()):
        pkg_name = os.path.dirname(fname) or '.'
        if pkg_name not in pkg_data:
            pkg_data[pkg_name] = {}
        pkg_data[pkg_name][fname] = lines

    for pkg_name, file_data in sorted(pkg_data.items()):
        pkg_elem = ET.SubElement(packages, 'package')
        pkg_elem.set('name', pkg_name)
        classes = ET.SubElement(pkg_elem, 'classes')

        pkg_lines = 0
        pkg_covered = 0

        for fname, lines in sorted(file_data.items()):
            cls_elem = ET.SubElement(classes, 'class')
            cls_elem.set('name', os.path.basename(fname))
            cls_elem.set('filename', fname)
            cls_elem.set('line-rate', '0')
            cls_elem.set('branch-rate', '0')
            cls_elem.set('complexity', '0')
            lines_elem = ET.SubElement(cls_elem, 'lines')

            file_lines = 0
            file_covered = 0
            for num, hits in sorted(lines.items(), key=lambda x: int(x[0])):
                line_elem = ET.SubElement(lines_elem, 'line')
                line_elem.set('number', str(num))
                line_elem.set('hits', str(hits))
                file_lines += 1
                if hits > 0:
                    file_covered += 1

            if file_lines > 0:
                cls_elem.set('line-rate', f'{file_covered / file_lines:.4f}')

            pkg_lines += file_lines
            pkg_covered += file_covered

        if pkg_lines > 0:
            pkg_elem.set('line-rate', f'{pkg_covered / pkg_lines:.4f}')
        else:
            pkg_elem.set('line-rate', '0')

        total_lines += pkg_lines
        covered_lines += pkg_covered

    if total_lines > 0:
        root.set('line-rate', f'{covered_lines / total_lines:.4f}')
    else:
        root.set('line-rate', '0')
    root.set('lines-valid', str(total_lines))
    root.set('lines-covered', str(covered_lines))

    tree = ET.ElementTree(root)
    ET.indent(tree, space='  ')
    tree.write(output_path, xml_declaration=True, encoding='utf-8')

    return total_lines, covered_lines


def main():
    parser = argparse.ArgumentParser(
        description='Merge cross-platform Cobertura XML coverage reports.')
    parser.add_argument('workspace', nargs='?',
                        default=os.environ.get('PIPELINE_WORKSPACE'),
                        help='Pipeline workspace root containing coverage-linux/ '
                             'and coverage-windows/ directories '
                             '(default: $PIPELINE_WORKSPACE)')
    parser.add_argument('output_dir', nargs='?',
                        default=os.environ.get('BUILD_SOURCESDIRECTORY'),
                        help='Output directory for coverage.xml '
                             '(default: $BUILD_SOURCESDIRECTORY)')
    args = parser.parse_args()

    if not args.workspace or not args.output_dir:
        parser.error('workspace and output_dir are required '
                     '(pass as arguments or set PIPELINE_WORKSPACE '
                     'and BUILD_SOURCESDIRECTORY environment variables)')

    workspace = args.workspace
    output_path = os.path.join(args.output_dir, 'coverage.xml')

    coverage_files = collect_coverage_files(workspace)

    if not coverage_files:
        print('No coverage files found')
        sys.exit(1)

    if len(coverage_files) == 1:
        shutil.copy(coverage_files[0][1], output_path)
        print(f'Using {coverage_files[0][0]} coverage as final report')
        sys.exit(0)

    coverage_data, file_sources = merge_coverage(coverage_files)

    # Print per-file source summary
    print(f'\n=== Coverage merge summary: {len(coverage_data)} files ===')
    for fname in sorted(coverage_data.keys()):
        sources = ', '.join(sorted(file_sources[fname]))
        lines_covered = sum(1 for h in coverage_data[fname].values() if h > 0)
        lines_total = len(coverage_data[fname])
        print(f'  {fname}: {lines_covered}/{lines_total} lines from [{sources}]')

    total_lines, covered_lines = write_cobertura_xml(coverage_data, output_path)

    if total_lines > 0:
        print(f'Merged coverage: {covered_lines}/{total_lines} lines covered '
              f'({covered_lines/total_lines*100:.1f}%)')
    else:
        print('No lines')
    print(f'Files: {len(coverage_data)}')
    print(f'Written to: {output_path}')


if __name__ == '__main__':
    main()
