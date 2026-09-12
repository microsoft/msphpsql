# Copyright (c) Microsoft Corporation. All rights reserved.
# Licensed under the MIT License.
"""Exercise the real macOS ODBC pipeline script without installing packages.

Requires Bash (set TEST_BASH to Git Bash on Windows) and requirements.txt.
The brew function below models tap-time trust enforcement, not Homebrew's
package installation. Actual macOS installation remains a CI check.
"""

import os
from pathlib import Path
import shutil
import subprocess

import pytest
import yaml

ROOT = Path(__file__).resolve().parents[2]
TAP = "microsoft/mssql-release"
INSTALL = f"install {TAP}/msodbcsql18 {TAP}/mssql-tools18"

BREW_STUB = r"""
trusted=${TEST_BREW_PRETRUSTED:-0}
tapped=0
installed=0
brew() {
    printf 'BREW:%s\n' "$*" >&2
    case "$1" in
        help)
            [[ "$*" == 'help trust' ]] || return 90
            [[ "$TEST_BREW_SUPPORTS_TRUST" == 1 ]]
            ;;
        trust)
            [[ "$*" == 'trust --tap microsoft/mssql-release' ]] || return 91
            [[ "$TEST_BREW_FAILURE" != trust ]] || return 21
            trusted=1
            ;;
        tap)
            [[ "$2" == microsoft/mssql-release ]] || return 92
            remote=https://github.com/Microsoft/homebrew-mssql-release
            [[ "$#" == 3 && "$3" == "$remote" ]] || return 92
            [[ "$TEST_BREW_FAILURE" != tap ]] || return 22
            if [[ "$TEST_BREW_REQUIRES_TRUST" == 1 && "$trusted" != 1 ]]; then
                echo 'Refusing to load formula from untrusted tap' >&2
                return 23
            fi
            tapped=1
            ;;
        install)
            [[ "$tapped" == 1 && "$HOMEBREW_ACCEPT_EULA" == Y ]] || return 93
            [[ "$#" == 3 ]] || return 94
            [[ "$2" == microsoft/mssql-release/msodbcsql18 ]] || return 94
            [[ "$3" == microsoft/mssql-release/mssql-tools18 ]] || return 94
            [[ "$TEST_BREW_FAILURE" != install ]] || return 24
            installed=1
            ;;
        list)
            [[ "$2" == --verbose && "$installed" == 1 ]] || return 95
            [[ "$TEST_BREW_FAILURE" != "list_$3" ]] || return 25
            if [[ "$TEST_BREW_FAILURE" == "list_empty_$3" ]]; then
                return 0
            fi
            printf '/test-cellar/%s/18.7.1.1\n' "$3"
            [[ "$TEST_BREW_FAILURE" != "list_partial_$3" ]] || return 26
            ;;
        reinstall)
            [[ "$*" == 'reinstall openssl@1.1' ]] || return 96
            # The existing optional OpenSSL workaround is allowed to fail.
            return 1
            ;;
        *) return 97 ;;
    esac
}
"""


@pytest.fixture(scope="module", name="install_script")
def fixture_install_script() -> str:
    pipeline = yaml.safe_load(
        (ROOT / "azure-pipelines.yml").read_text(encoding="utf-8")
    )
    jobs = [job for job in pipeline["jobs"] if job.get("job") == "macOS"]
    assert len(jobs) == 1
    steps = [
        step
        for step in jobs[0]["steps"]
        if step.get("displayName") == "Install ODBC Driver 18 and Tools"
    ]
    assert len(steps) == 1
    script = steps[0]["script"]
    assert isinstance(script, str)
    return script


@pytest.fixture(scope="module", name="bash")
def fixture_bash() -> str:
    executable = os.environ.get("TEST_BASH") or shutil.which("bash")
    assert executable, "Bash is required; set TEST_BASH to its executable"
    return executable


def run_install(
    script: str,
    bash: str,
    directory: Path,
    *,
    supports_trust: bool = True,
    requires_trust: bool = True,
    pretrusted: bool = False,
    failure: str = "",
) -> subprocess.CompletedProcess[str]:
    # Isolate inherited shell startup hooks and Homebrew settings. Never run
    # real brew: the stub handles every invocation from the extracted step.
    excluded = {"BASH_ENV", "ENV", "SHELLOPTS", "BASHOPTS"}
    env = {
        key: value
        for key, value in os.environ.items()
        if key not in excluded
        and not key.startswith(("HOMEBREW_", "TEST_BREW_", "BASH_FUNC_"))
    }
    env.update(
        TEST_BREW_SUPPORTS_TRUST=str(int(supports_trust)),
        TEST_BREW_REQUIRES_TRUST=str(int(requires_trust)),
        TEST_BREW_PRETRUSTED=str(int(pretrusted)),
        TEST_BREW_FAILURE=failure,
    )
    return subprocess.run(
        [bash, "--noprofile", "--norc", "-s"],
        input=BREW_STUB + "\n" + script,
        text=True,
        encoding="utf-8",
        capture_output=True,
        cwd=directory,
        env=env,
        timeout=15,
        check=False,
    )


def calls(result: subprocess.CompletedProcess[str]) -> list[str]:
    return [
        line.removeprefix("BREW:")
        for line in result.stderr.splitlines()
        if line.startswith("BREW:")
    ]


@pytest.mark.parametrize("pretrusted", [False, True])
def test_trust_precedes_tap(
    install_script: str, bash: str, tmp_path: Path, pretrusted: bool
) -> None:
    result = run_install(install_script, bash, tmp_path, pretrusted=pretrusted)
    assert result.returncode == 0, result.stderr
    commands = calls(result)
    trust = f"trust --tap {TAP}"
    tap = f"tap {TAP} https://github.com/Microsoft/homebrew-mssql-release"
    assert commands.index(trust) < commands.index(tap)
    assert commands.index(tap) < commands.index(INSTALL)
    assert "list --verbose msodbcsql18" in commands
    assert "list --verbose mssql-tools18" in commands


def test_older_homebrew_without_trust_command(
    install_script: str, bash: str, tmp_path: Path
) -> None:
    result = run_install(
        install_script,
        bash,
        tmp_path,
        supports_trust=False,
        requires_trust=False,
    )
    assert result.returncode == 0, result.stderr
    assert INSTALL in calls(result)
    assert not any(call.startswith("trust ") for call in calls(result))


@pytest.mark.parametrize(
    ("failure", "blocked_command"),
    [
        ("trust", "tap "),
        ("tap", "install "),
        ("install", "list "),
        ("list_msodbcsql18", "reinstall "),
        ("list_mssql-tools18", "reinstall "),
        ("list_empty_msodbcsql18", "reinstall "),
        ("list_empty_mssql-tools18", "reinstall "),
        ("list_partial_msodbcsql18", "reinstall "),
        ("list_partial_mssql-tools18", "reinstall "),
    ],
)
def test_setup_fails_closed(
    install_script: str,
    bash: str,
    tmp_path: Path,
    failure: str,
    blocked_command: str,
) -> None:
    result = run_install(install_script, bash, tmp_path, failure=failure)
    assert result.returncode != 0
    assert not any(
        command.startswith(blocked_command) for command in calls(result)
    )
    assert "##vso[task.prependpath]" not in result.stdout


def test_missing_trust_capability_cannot_bypass_enforcement(
    install_script: str, bash: str, tmp_path: Path
) -> None:
    result = run_install(install_script, bash, tmp_path, supports_trust=False)
    assert result.returncode != 0
    assert "untrusted tap" in result.stderr
    assert INSTALL not in calls(result)


def test_no_global_trust_bypass(install_script: str) -> None:
    assert "HOMEBREW_NO_REQUIRE_TAP_TRUST" not in install_script
    assert "brew trust --formula" not in install_script
    assert "trust --tap microsoft/mssql-release" in install_script


def test_path_with_spaces(
    install_script: str, bash: str, tmp_path: Path
) -> None:
    directory = tmp_path / "pipeline checkout with spaces"
    directory.mkdir()
    result = run_install(install_script, bash, directory)
    assert result.returncode == 0, result.stderr


def test_bash_syntax(install_script: str, bash: str) -> None:
    result = subprocess.run(
        [bash, "--noprofile", "--norc", "-n"],
        input=install_script,
        text=True,
        capture_output=True,
        timeout=15,
        check=False,
    )
    assert result.returncode == 0, result.stderr
