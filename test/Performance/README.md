## Setup Environment on a clean machine

The PHP zip file can be downloaded from <http://php.net/downloads.php>.

The SQLSRV and PDO_SQLSRV driver binaries can be downloaded from <https://github.com/Microsoft/msphpsql/releases>.

### Windows
Install Visual Studio 2015 redistributable before running the following commands.
Run `Windows PowerShell` as administrator.

    Set-ExecutionPolicy Unrestricted
    .\setup_env_windows.ps1 <absolute path to the PHP zip file> <absolute path to the SQLSRV driver DLL> <absolute path to the PDO_SQLSRV driver DLL>

The Windows setup script is intended for a clean benchmark machine using Windows
PowerShell 5.1, not PowerShell Core. It keeps PHP in `C:\php` and
preserves the existing Machine PATH, including installer-added entries. It adds
only the verified PHP and Git `cmd` directories to Machine PATH so that
`run-perf_tests.py` can still invoke `php` in fresh shells. Process-only and user
PATH entries are not copied into Machine PATH; existing machine entries are not
removed or reordered.
Expandable references such as `%JAVA_HOME%\bin` are read without expansion and
retain their registry value kind when saved. Setup broadcasts the Windows
environment-change notification after writing Machine PATH.

Setup checks Chocolatey, pip, and Composer exit codes. Before using the selected
tools, it checks their ownership, write/replacement permissions, ancestors, and
reparse points. Recursive checks cover the selected Python/PHP installations
(including executable and extension/module files) and the Git `cmd` directory,
not all installed Chocolatey packages or the entire Git installation.
Administrators, SYSTEM, and TrustedInstaller are trusted owners/writers.
Ancestor checks reject replacement and permission-control rights, not ordinary
sibling-creation rights such as the default permissions on `C:\ProgramData`.
The executable's immediate containing directory uses the stricter target rules,
so it cannot admit non-administrator-created DLLs or configuration files.
An existing `C:\php` must be trusted before replacement; failed cleanup stops
setup. The replacement directory grants Administrators/SYSTEM full control and
Users read/execute through an administrator-owned protected DACL.

After Chocolatey successfully installs Python, setup reads its installed
`python3.nuspec` version and resolves that version's machine-wide registration,
using Chocolatey's default OS architecture. Other Python versions may coexist;
a protected installation outside `C:\Python*` is supported. Missing metadata,
registration, or interpreter causes an explicit error, not a wildcard/PATH
fallback. The interpreter runs `-I -m pip` to exclude working-directory and user
Python modules. Because `-I` does not disable pip configuration, setup also sets
`PIP_CONFIG_FILE=nul` only for the pip invocation, restoring the previous process
value (or its absence) even on failure. Global, user, and site pip configuration
files are therefore not used; other explicit pip environment settings are unchanged.
Composer uses `C:\php\php.exe`. Package versions, the ODBC MSI
command, and Windows configuration/library copies are unchanged.

To run the setup regression checks in Windows PowerShell with Pester 3.4, use an
environment whose script-signing policy permits these repository tests. Do not
relax a shared host's execution policy to run them.

    Invoke-Pester -Script .\setup_env_windows.Tests.ps1 -EnableExit

These checks load only the setup functions, use in-memory security descriptors
and mocked filesystem/registry operations, and run harmless `cmd.exe` exit-code
probes. They never run provisioning or change Machine PATH or filesystem ACLs.
The Windows performance setup tests workflow runs them in both 32-bit and 64-bit
Windows PowerShell on a GitHub-hosted Windows runner for changes to the setup scripts.
Both runners stop on Pester load errors and reject missing or zero-test results.
An elevated end-to-end installation and effective-permission checks still
require a disposable Windows benchmark machine; unit checks do not establish
that an existing host's installation is safe.

### Ubuntu 16
    sudo env "PATH=$PATH" bash setup_env_unix.sh Ubuntu16 <PHP_VERSION - 7.x.y> <PHP_THREAD - ts or nts> <absolute path to the SQLSRV driver so> <absolute path to the PDO_SQLSRV driver so>
### RedHat 7
    sudo env "PATH=$PATH" bash setup_env_unix.sh RedHat7 <PHP_VERSION - 7.x.y> <PHP_THREAD - ts or nts> <absolute path to the SQLSRV driver so> <absolute path to the PDO_SQLSRV driver so>
### SUSE 12
    sudo env "PATH=$PATH" bash setup_env_unix.sh SUSE12 <PHP_VERSION - 7.x.y> <PHP_THREAD - ts or nts> <absolute path to the SQLSRV driver so> <absolute path to the PDO_SQLSRV driver so>
### MacOS 10.12 Sierra
`brew` cannot be run with `sudo` on Sierra. Either enable passwordless `sudo` on the machine or enter the password when prompted. 

    bash setup_env_unix.sh Sierra <PHP_VERSION - 7.x.y> <PHP_THREAD - ts or nts> <absolute path to the SQLSRV driver so> <absolute path to the PDO_SQLSRV driver so>
## Run benchmarks
PHPBench is used to run the benchmarks. Visit http://phpbench.readthedocs.io/en/latest/introduction.html to have an idea how the tool works.

##### 1. Modify lib/connect.php with the test database credentials
##### 2. Modify lib/result_db.php with the result database credentials
##### 3. The number of iterations for each test can be modified in the test itself (e.g., in test/Performance/benchmark/sqlsrv/SqlsrvSelectVersionBench.php). Each bench class in a test, or the parent class that it extends from, has a @Iteration(n) annotation. If you change the number in this annotation, you will change the number of iterations run for this test. By default, most tests are set to 1000 iterations.
##### 4. Execute run-perf_tests.py. 

### Windows
    py.exe run-perf_tests.py -platform <PLATFORM>
### Linux and Mac
On Linux and Mac, the script must be executed with `sudo python3` because to enable pooling it needs to modify odbcinst.ini system file. As an improvement, the location of the odbcinst.ini file can be changed so that sudo is not required. 
    
    python3 run-perf_tests.py -platform <PLATFORM> | tee run-perf_output.txt

`-platform` - The platform that the tests are ran on. Must be one of the following: Windows10, WindowsServer2016, WindowsServer2012, Ubuntu16, RedHat7, SUSE12, Sierra.
`-php-driver` (optional) - The driver that the tests are ran on. Must be one of the following: sqlsrv, pdo_sqlsrv, or both. Default is both.
`-testname` (optional) - The test to run. Must be the file name (not including path) of one test or 'all'. Default is 'all'. If one test is specified, must also specify the -php-driver option to sqlsrv or pdo_sqlsrv.