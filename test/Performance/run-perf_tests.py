import shutil
import os
import sys

def check_exe(name):
    path_to_exe = shutil.which(name)
    if path_to_exe is not None:
        print(name + " found: " + path_to_exe)
    else:
        sys.exit(name + " not found. Exiting...")


print("Checking requirements...\n")

check_exe("php")

print("\nPHP INFO:\n")
os.system("php -v")

print("\nChecking if sqlsrv and pdo_sqslrv are loaded...\n")
if os.system("php --ri sqlsrv") != 0:
    sys.exit("Exiting...")
if os.system("php --ri pdo_sqlsrv") != 0:
    sys.exit("Exiting...")

print("Installing Composer...")
os.system("php download_composer_setup.php")
os.system("php composer-setup.php")
os.system("php unlink_composer_setup.php")

print("Installing PHPBench...")
os.system("php composer.phar install")