#!/usr/bin/env python3
"""Exercise the Linux PHP provisioning step without installing anything."""

import os
from pathlib import Path
import shutil
import subprocess
import textwrap
import unittest


PIPELINE = Path(__file__).resolve().parents[2] / "azure-pipelines.yml"
MOCKS = r"""
sudo() {
    printf 'sudo %s\n' "$*" >&2
    case "$1" in
        add-apt-repository) [[ "$SCENARIO" != repository_failure ]] ;;
        apt-get)
            [[ "$*" == *Acquire::Retries=3* ]] || return 1
            if [[ "$SCENARIO" == apt_update_failure && "$*" == *" update" ]]; then return 1; fi
            if [[ "$SCENARIO" == apt_install_failure && "$*" == *" install "* ]]; then return 1; fi
            return 0 ;;
        update-alternatives) [[ "$SCENARIO" != "alternatives_$3" ]] ;;
        mkdir|phpdismod) return 0 ;;
        *) echo "Unexpected sudo command" >&2; return 1 ;;
    esac
}
python3() { :; }
readlink() {
    case "$2" in
        php|phpize|php-config)
            if [[ "$SCENARIO" == "link_$2" ]]; then
                printf '/usr/bin/%s8.3\n' "$2"
            else
                printf '/usr/bin/%s8.5\n' "$2"
            fi ;;
        *) printf '%s\n' "$2" ;;
    esac
}
php() {
    if [[ "$1" == -r && "$2" == *PHP_MAJOR_VERSION* ]]; then
        if [[ "$SCENARIO" == wrong_minor ]]; then echo 8.3; else echo 8.5; fi
    else
        echo 8.5.12
    fi
}
php-config() {
    case "$1" in
        --version)
            if [[ "$SCENARIO" == wrong_patch ]]; then echo 8.5.11; else echo 8.5.12; fi ;;
        --phpapi)
            if [[ "$SCENARIO" != missing_api ]]; then echo 20250925; fi ;;
        *) return 1 ;;
    esac
}
phpize() {
    if [[ "$SCENARIO" == phpize_failure ]]; then return 1; fi
    if [[ "$SCENARIO" == wrong_api ]]; then
        echo 'PHP Api Version: 20230831'
    else
        echo 'PHP Api Version: 20250925'
    fi
}
"""


class LinuxPHPBootstrapTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.bash = shutil.which("bash")
        if cls.bash is None:
            raise RuntimeError("Bash is required to exercise the Linux PHP bootstrap.")
        pipeline = PIPELINE.read_text(encoding="utf-8")
        end = pipeline.index("    displayName: 'Install PHP $(phpver)'")
        start = pipeline.rindex("  - script: |\n", 0, end) + len("  - script: |\n")
        cls.script = textwrap.dedent(pipeline[start:end]).replace("$(phpver)", "8.5")

    def run_bootstrap(self, scenario):
        return subprocess.run(
            [self.bash, "--noprofile", "--norc", "-s"],
            input=MOCKS + self.script + "\necho BOOTSTRAP_VERIFIED\n",
            text=True,
            capture_output=True,
            env={**os.environ, "SCENARIO": scenario},
            timeout=10,
        )

    def test_matching_runtime_and_development_tools(self):
        result = self.run_bootstrap("success")
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        self.assertIn("BOOTSTRAP_VERIFIED", result.stdout)
        self.assertIn("Acquire::Retries=3 -o APT::Update::Error-Mode=any update", result.stderr)
        self.assertIn("Acquire::Retries=3 install -y php8.5-cli php8.5-dev", result.stderr)

    def test_failed_installation_cannot_reach_alternatives_or_build(self):
        for scenario in ("repository_failure", "apt_update_failure", "apt_install_failure"):
            with self.subTest(scenario=scenario):
                result = self.run_bootstrap(scenario)
                self.assertNotEqual(result.returncode, 0)
                self.assertNotIn("update-alternatives", result.stderr)
                self.assertNotIn("BOOTSTRAP_VERIFIED", result.stdout)

    def test_tool_selection_and_identity_fail_closed(self):
        scenarios = [
            "wrong_minor", "wrong_patch", "wrong_api", "missing_api", "phpize_failure",
            *("alternatives_" + tool for tool in ("php", "phpize", "php-config")),
            *("link_" + tool for tool in ("php", "phpize", "php-config")),
        ]
        for scenario in scenarios:
            with self.subTest(scenario=scenario):
                result = self.run_bootstrap(scenario)
                self.assertNotEqual(result.returncode, 0, result.stdout + result.stderr)
                self.assertNotIn("BOOTSTRAP_VERIFIED", result.stdout)


if __name__ == "__main__":
    unittest.main()
