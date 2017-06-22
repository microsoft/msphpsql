## Setup Environment on a clean machine

### Windows
Install Visual Studio 2015 before running the following commands. Make sure C++ tools are enabled.
Run `cmd` as administrator.

    powershell
    Set-ExecutionPolicy Unrestricted
    .\setup_env_windows.ps1 <PHP_VERSION - 7.x.x> <PHP_THREAD - ts or nts> <Absolute path to driver source folder> <ARCH - x86 or x64>   
If `PHP_VERSION` is wrong, the script will default it to the latest PHP 7.1 version    

### Ubuntu 16
    sudo env “PATH=$PATH” bash setup_env_unix.sh Ubuntu16 <PHP_VERSION - 7.x.x> <PHP_THREAD - ts or nts> <Absolute path to driver source folder>
### RedHat 7
    sudo env “PATH=$PATH” bash setup_env_unix.sh RedHat7 <PHP_VERSION - 7.x.x> <PHP_THREAD - ts or nts> <Absolute path to driver source folder>
### Sierra
`brew` cannot be run with `sudo` on Sierra. Either enable passwordless `sudo` on the machine or enter the password when prompted. 

    bash setup_env_unix.sh Sierra <PHP_VERSION - 7.x.x> <PHP_THREAD - ts or nts> <Absolute path to driver source folder>
## Run benchmarks

### 1. Modify lib/connect.php with the test database credetials
### 2. Execute run-perf_tests.py. 
The script must be executed with `sudo` because to enable pooling it needs to modify odbcinst.ini system file. As an improvement, the location of the odbcinst.ini file can be changed so that, sudo is not requiered. 
    
    run-perf_tests.py -platform <PLATFORM> -iterations <ITERATIONS> -iterations-large <ITERATIONS_LARGE> -result-server <RESULT_SERVER> -result-db <RESULT_DB> -result-uid <RESULT_UID> -result-pwd <RESULT_PWD>

`-platform` - The platform that the tests are ran on. Must be one of the following: Windows10, WidnowsServer2016 WindowsServer2012 Ubuntu16 RedHat7 Sierra  
`-iterations` - The number of iterations for regular tests.  
`-iterations-large` - The number of iterations for the tests that fetch large data. Usually set to 1.  
`-result-server` - The server of result database. It is assumed that, the result database already setup before running the tests.  
`-result-db` - Database name. With the current result database setup files, this should be set to `TestResults`  
`-result-uid` - Result database username  
`-result-pwd` Result database password  


    
