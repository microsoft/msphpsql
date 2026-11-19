#!/usr/bin/env python3
"""Exercise macOS CI setup and build coordination without Homebrew, Docker or PHP."""

import os
from pathlib import Path
import re
import shutil
import subprocess
import tempfile
import textwrap
import unittest


PIPELINE = Path(__file__).resolve().parents[2] / "azure-pipelines.yml"
MOCKS = r"""
brew() {
    case "$1" in
        list)
            if [[ "$SCENARIO" != reuse && ( "$4" == docker || "$4" == colima ) ]]; then
                return 1
            fi
            echo "$4 1.0" ;;
        install)
            echo "INSTALL $*"
            [[ "$SCENARIO" != install_failure ]] ;;
        *) echo "Unexpected brew command: $*" >&2; return 1 ;;
    esac
}
colima() {
    echo "COLIMA_LOG"
    [[ "$SCENARIO" != colima_failure ]] || return 7
    for ((i=0; i<100; i++)); do
        [[ ! -f "$TEST_ROOT/build-started" ]] || return 0
        command sleep 0.05
    done
    echo "Build did not start alongside Colima" >&2
    return 1
}
docker() {
    echo "DOCKER $*"
    case "$1" in
        info) [[ "$SCENARIO" != docker_failure ]] ;;
        run)
            [[ "$SA_PASSWORD" == "$SQLCMDPASSWORD" && "$*" == *"-e SA_PASSWORD"* ]] || return 1
            [[ "$SCENARIO" != container_failure ]] ;;
        logs) echo "CONTAINER_DIAGNOSTICS" ;;
        *) return 1 ;;
    esac
}
sqlcmd() {
    [[ "$SQLCMDPASSWORD" == mock-password && "$*" != *" -P "* ]] || return 1
    [[ "$*" == *"-b -l 5 -t 5"* ]] || return 1
    echo probe >> "$TEST_ROOT/probes"
    touch "$TEST_ROOT/sql-probed"
    if [[ "$SCENARIO" == readiness_timeout ]]; then return 1; fi
    if [[ "$SCENARIO" == delayed_ready && $(wc -l < "$TEST_ROOT/probes") -lt 3 ]]; then
        return 1
    fi
    echo "SQL_READY"
}
make() {
    echo "MAKE"
    [[ "$SCENARIO" != make_failure ]]
}
sysctl() { echo 2; }
sleep() { :; }
mktemp() {
    : > "$TEST_ROOT/setup.log"
    echo "$TEST_ROOT/setup.log"
}
"""
BUILDCONF = r"""#!/usr/bin/env bash
touch "$TEST_ROOT/build-started"
if [[ "$SCENARIO" == buildconf_failure ]]; then exit 17; fi
if [[ "$SCENARIO" == success || "$SCENARIO" == delayed_ready ]]; then
    for ((i=0; i<100; i++)); do
        if [[ -f "$TEST_ROOT/sql-probed" ]]; then exit 0; fi
        sleep 0.05
    done
    echo "SQL setup did not run alongside buildconf" >&2
    exit 1
fi
"""


class MacOSCISetupTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.bash = shutil.which("bash")
        if cls.bash is None:
            raise RuntimeError("Bash is required to exercise the macOS CI setup.")
        cls.pipeline = PIPELINE.read_text(encoding="utf-8").split("- job: Linux\n")[0]
        cls.build = cls.step("Build PHP with Drivers and Start SQL Server")
        prerequisites = cls.step("Install Prerequisites")
        cls.install = prerequisites[
            prerequisites.index("missing=()"):prerequisites.index('echo "Setting up environment..."')
        ]

    @classmethod
    def step(cls, name):
        end = cls.pipeline.index(f"    displayName: '{name}'")
        start = cls.pipeline.rindex("  - script: |\n", 0, end) + len("  - script: |\n")
        return textwrap.dedent(cls.pipeline[start:end])

    def run_script(self, script, scenario, root):
        script = script.replace("$(Build.SourcesDirectory)", "$TEST_ROOT")
        script = script.replace("$(sqlUser)", "sa").replace("$(phpver)", "8.5")
        return subprocess.run(
            [self.bash, "--noprofile", "--norc", "-s"],
            input=MOCKS + "\nset -e\n" + script + "\necho STEP_VERIFIED\n",
            text=True,
            capture_output=True,
            env={
                **os.environ,
                "SCENARIO": scenario,
                "TEST_ROOT": root.as_posix(),
                "SQLCMDPASSWORD": "mock-password",
            },
            timeout=15,
        )

    def run_build(self, scenario):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            php = root / "php-src"
            (php / "modules").mkdir(parents=True)
            for name in ("sqlsrv.so", "pdo_sqlsrv.so"):
                (php / "modules" / name).touch()
            scripts = {
                "buildconf": BUILDCONF,
                "configure": '#!/usr/bin/env bash\n[[ "$SCENARIO" != configure_failure ]]\n',
            }
            for name, body in scripts.items():
                path = php / name
                path.write_text(body, encoding="utf-8", newline="\n")
                path.chmod(0o755)
            result = self.run_script(self.build, scenario, root)
            self.assertFalse((root / "setup.log").exists(), result.stdout + result.stderr)
            self.assertNotIn("mock-password", result.stdout + result.stderr)
            probes = root / "probes"
            return result, len(probes.read_text().splitlines()) if probes.exists() else 0

    def test_installed_tools_are_reused_and_missing_tools_installed_once(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            reused = self.run_script(self.install, "reuse", root)
            self.assertEqual(reused.returncode, 0, reused.stdout + reused.stderr)
            self.assertNotIn("INSTALL", reused.stdout)
            missing = self.run_script(self.install, "missing", root)
            self.assertEqual(missing.returncode, 0, missing.stdout + missing.stderr)
            self.assertEqual(missing.stdout.count("INSTALL"), 1)
            self.assertIn("INSTALL install --formula docker colima", missing.stdout)
            failed = self.run_script(self.install, "install_failure", root)
            self.assertNotEqual(failed.returncode, 0)
            self.assertNotIn("STEP_VERIFIED", failed.stdout)

    def test_sql_setup_overlaps_build_and_retries_until_ready(self):
        for scenario, count in (("success", 1), ("delayed_ready", 3)):
            with self.subTest(scenario=scenario):
                result, probes = self.run_build(scenario)
                self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
                self.assertEqual(probes, count)
                self.assertIn("STEP_VERIFIED", result.stdout)
                self.assertIn("SQL_READY", result.stdout)

    def test_setup_failures_cannot_be_hidden_by_successful_build(self):
        for scenario in ("colima_failure", "docker_failure", "container_failure", "readiness_timeout"):
            with self.subTest(scenario=scenario):
                result, probes = self.run_build(scenario)
                self.assertNotEqual(result.returncode, 0, result.stdout + result.stderr)
                self.assertNotIn("STEP_VERIFIED", result.stdout)
                self.assertIn("SQL Server setup failed", result.stdout)
                self.assertIn("COLIMA_LOG", result.stdout)
                if scenario == "readiness_timeout":
                    self.assertEqual(probes, 60)
                    self.assertIn("CONTAINER_DIAGNOSTICS", result.stdout)
                else:
                    self.assertEqual(probes, 0)

    def test_build_failures_still_join_setup_and_print_its_log(self):
        for scenario in ("buildconf_failure", "configure_failure", "make_failure"):
            with self.subTest(scenario=scenario):
                result, probes = self.run_build(scenario)
                self.assertNotEqual(result.returncode, 0, result.stdout + result.stderr)
                self.assertNotIn("STEP_VERIFIED", result.stdout)
                self.assertEqual(probes, 1)
                self.assertIn("SQL_READY", result.stdout)

    def test_macos_shell_syntax(self):
        for script in re.findall(r"  - script: \|\n(.*?)    displayName:", self.pipeline, re.S):
            result = subprocess.run(
                [self.bash, "--noprofile", "--norc", "-n"],
                input=textwrap.dedent(script),
                text=True,
                capture_output=True,
                timeout=10,
            )
            self.assertEqual(result.returncode, 0, result.stderr)


if __name__ == "__main__":
    unittest.main()
