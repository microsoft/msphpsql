## 1. Install Composer - Dependency Manager for PHP  
https://getcomposer.org/

    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php -r "if (hash_file('SHA384', 'composer-setup.php') === '669656bab3166a7aff8a7506b8cb2d1c292f042046c5a994c43155c0be6190fa0355160742ab2e1c88d40d5be660b410') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    php composer-setup.php
    php -r "unlink('composer-setup.php');"
    
## 2. Install PHPBench - A Benchmark Runner for PHP
http://phpbench.readthedocs.io/en/latest/

Make sure git is installed and in your PATH env. Navigate into Performance folder and run:

    composer install

## 3. Run benchmarks

PHPBench will use the default PHP in PATH env. To specify a different PHP binary, use `--php-binary path_to_php`. Make sure `sqlsrv` and `pdo_sqlsrv` are loaded. Edit `lib/connect.php` with connection credentials. Run benchmarks. 

Run sqlsrv benchmarks:

    ./vendor/bin/phpbench run benchmark/sqlsrv/regular --time-unit="milliseconds" --iterations [num_of_iterations] --report=aggregate
    
Run pdo_sqlsrv benchmarks:

    ./vendor/bin/phpbench run benchmark/pdo_sqlsrv/regular --time-unit="milliseconds" --iterations [num_of_iterations] --report=aggregate
    
Run benchmarks that fetch large dataset. These benchmarks assume the database is already populated with data. 
    
    ./vendor/bin/phpbench run benchmark/sqlsrv/large --time-unit="milliseconds" --report=aggregate
    ./vendor/bin/phpbench run benchmark/pdo_sqlsrv/large --time-unit="milliseconds" --report=aggregate
