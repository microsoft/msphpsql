## Setup Environment on a clean machine

### Windows
Install Visual Studio 2015 before running the following commands. Make sure C++ tools are enabled.
Run `cmd` as administrator.

    powershell
    .\setup_env_windows.ps1 <LATEST PHP_VERSION - 7.x.x> <PHP_THREAD - ts or nts> <Absolute path to driver source folder> <ARCH - x86 or x64>    
### Ubuntu 16
    sudo env “PATH=$PATH” bash setup_env_unix.sh Ubuntu16 <PHP_VERSION - 7.x.x> <PHP_THREAD - ts or nts> <Absolute path to driver source folder>
### RedHat 7
    sudo env “PATH=$PATH” bash setup_env_unix.sh RedHat7 <PHP_VERSION - 7.x.x> <PHP_THREAD - ts or nts> <Absolute path to driver source folder>
### Sierra
`brew` cannot be run with `sudo` on Sierra. Either enable passwordless `sudo` on the machine or enter the password when prompted. 

    bash setup_env_unix.sh Sierra <PHP_VERSION - 7.x.x> <PHP_THREAD - ts or nts> <Absolute path to driver source folder>
## Run benchmarks - Subject to change once the process is automated

Run sqlsrv benchmarks:

    ./vendor/bin/phpbench run benchmark/sqlsrv/regular --time-unit="milliseconds" --iterations [num_of_iterations] --report=aggregate
    
Run pdo_sqlsrv benchmarks:

    ./vendor/bin/phpbench run benchmark/pdo_sqlsrv/regular --time-unit="milliseconds" --iterations [num_of_iterations] --report=aggregate
    
Run benchmarks that fetch large dataset. These benchmarks assume the database is already populated with data. 
    
    ./vendor/bin/phpbench run benchmark/sqlsrv/large --time-unit="milliseconds" --report=aggregate
    ./vendor/bin/phpbench run benchmark/pdo_sqlsrv/large --time-unit="milliseconds" --report=aggregate
