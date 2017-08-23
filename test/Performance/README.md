## Setup Environment on a clean machine

The PHP zip file can be downloaded from <http://php.net/downloads.php>.

The SQLSRV and PDO_SQLSRV driver binaries can be downloaded from <https://github.com/Microsoft/msphpsql/releases>.

### Windows
Install Visual Studio 2015 redistributable before running the following commands.
Run `Windows PowerShell` as administrator.

    Set-ExecutionPolicy Unrestricted
    .\setup_env_windows.ps1 <absolute path to the PHP zip file> <absolute path to the SQLSRV driver DLL> <absolute path to the PDO_SQLSRV driver DLL>

### Ubuntu 16
    sudo env "PATH=$PATH" bash setup_env_unix.sh Ubuntu16 <PHP_VERSION - 7.x.y> <PHP_THREAD - ts or nts> <absolute path to the SQLSRV driver so> <absolute path to the PDO_SQLSRV driver so>
### RedHat 7
    sudo env "PATH=$PATH" bash setup_env_unix.sh RedHat7 <PHP_VERSION - 7.x.y> <PHP_THREAD - ts or nts> <absolute path to the SQLSRV driver so> <absolute path to the PDO_SQLSRV driver so>
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
On Linux and Mac, the script must be executed with `sudo python3` because to enable pooling it needs to modify odbcinst.ini system file. As an improvement, the location of the odbcinst.ini file can be changed so that, sudo is not requiered. 
    
    python3 run-perf_tests.py -platform <PLATFORM> | tee run-perf_output.txt

`-platform` - The platform that the tests are ran on. Must be one of the following: Windows10, WindowsServer2016, WindowsServer2012, Ubuntu16, RedHat7, Sierra.
`-php-driver` (optional) - The driver that the tests are ran on. Must be one of the following: sqlsrv, pdo_sqlsrv, or both. Default is both.
`-testname` (optional) - The test to run. Must be the file name (not including path) of one test or 'all'. Default is 'all'. If one test is specified, must also specify the -php-driver option to sqlsrv or pdo_sqlsrv.