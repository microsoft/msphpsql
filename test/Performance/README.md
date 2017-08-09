## Setup Environment on a clean machine

### Windows
Install Visual Studio 2015 before running the following commands. Make sure C++ tools are enabled.
Run `cmd` as administrator.

    powershell
    Set-ExecutionPolicy Unrestricted
    .\setup_env_windows.ps1 <PHP_VERSION - 7.x.x> <PHP_THREAD - ts or nts> <Absolute path to driver source folder> <ARCH - x86 or x64>    

### Ubuntu 16
    sudo env "PATH=$PATH" bash setup_env_unix.sh Ubuntu16 <PHP_VERSION - 7.x.x> <PHP_THREAD - ts or nts> <Absolute path to driver source folder>
### RedHat 7
    sudo env "PATH=$PATH" bash setup_env_unix.sh RedHat7 <PHP_VERSION - 7.x.x> <PHP_THREAD - ts or nts> <Absolute path to driver source folder>
### Sierra
`brew` cannot be run with `sudo` on Sierra. Either enable passwordless `sudo` on the machine or enter the password when prompted. 

    bash setup_env_unix.sh Sierra <PHP_VERSION - 7.x.x> <PHP_THREAD - ts or nts> <Absolute path to driver source folder>
## Run benchmarks
PHPBench is used to run the benchmarks. Visit http://phpbench.readthedocs.io/en/latest/introduction.html to have an idea how the tool works.

### 1. Modify lib/connect.php with the test database credentials
### 2. Modify lib/result_db.php with the result database credentials
### 3. The number of iterations for each test can be modified in the test itself (e.g., in test/Performance/benchmark/sqlsrv). Each test has a @Iteration(n) annotation. If you change the number in this annotation, you will change the number of iterations run for this test. By default, most tests are set to 1000 iterations.
### 4. Execute run-perf_tests.py. 
### Windows
    py.exe run-perf_tests.py -platform <PLATFORM> >> run-perf_output.txt
### Linux and Mac
On Linux and Mac, the script must be executed with `sudo python3` because to enable pooling it needs to modify odbcinst.ini system file. As an improvement, the location of the odbcinst.ini file can be changed so that, sudo is not requiered. 
    
    python3 run-perf_tests.py -platform <PLATFORM> >> run-perf_output.txt

`-platform` - The platform that the tests are ran on. Must be one of the following: Windows10, WindowsServer2016, WindowsServer2012, Ubuntu16, RedHat7, Sierra
`-php-driver` (optional) - The driver that the tests are ran on. Must be one of the following: sqlsrv, pdo_sqlsrv, or both. Default is both.
`-test-only` (optional) - The test to run. Must be the file name (not including path) of one test or 'all'. Default is 'all'. If one test is specified, must also specify the -php-driver option to sqlsrv or pdo_sqlsrv.