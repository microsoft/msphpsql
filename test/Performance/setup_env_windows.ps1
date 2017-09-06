Param(
    [Parameter(Mandatory=$True,Position=1)]
	[string]$PHP_ZIP,
	[Parameter(Mandatory=$True,Position=2)]
    [string]$SQLSRV_DRIVER,
    [Parameter(Mandatory=$True,Position=3)]
    [string]$PDO_DRIVER
    )

$ErrorActionPreference = "Stop"

$startingDir=$pwd.Path
$tempFolder=Join-Path $startingDir "temp"

Remove-Item temp -Recurse -Force -ErrorAction Ignore
New-Item -ItemType directory -Path temp

Write-Host "Installing chocolatey..."
iex ((New-Object System.Net.WebClient).DownloadString('https://chocolatey.org/install.ps1'))
Write-Host "Installing Git..."
choco install -y git

$gitDir = 'C:\Program Files\Git\cmd\git.exe'
Write-Host "Installing Python3..."
choco install -y python3
RefreshEnv
Write-Host "Installing pyodbc..."
C:\Python*\Scripts\pip3.exe install pyodbc | Out-Null
Write-Host "Downloading MSODBCSQL..."
#This needs to be manually updated when there is a new release
(New-object System.Net.WebClient).DownloadFile('https://download.microsoft.com/download/D/5/E/D5EEF288-A277-45C8-855B-8E2CB7E25B96/x64/msodbcsql.msi', "$tempFolder\msodbcsql.msi")
Write-Host "Installing MSODBCSQL..."
msiexec /quiet /passive /qn /i $tempFolder\msodbcsql.msi IACCEPTMSODBCSQLLICENSETERMS=YES | Out-Null
Write-Host "Installing 7-Zip..."
choco install -y 7zip.install

Write-Host "Installing PHP..."
$phpDir="C:\php"

# remove existing PHP and setup new one
Remove-Item $phpDir -Recurse -ErrorAction Ignore
New-Item -ItemType directory -Path $phpDir
Expand-Archive $PHP_ZIP -DestinationPath $phpDir

# copy drivers to extensions directory and rename to a standard nomenclature
# for consistency with run-perf_tests.py
Copy-Item $SQLSRV_DRIVER $phpDir\ext\php_sqlsrv.dll
Copy-Item $PDO_DRIVER $phpDir\ext\php_pdo_sqlsrv.dll

# setup driver
Copy-Item $phpDir\php.ini-production $phpDir\php.ini
Add-Content $phpDir\php.ini "extension=$phpDir\ext\php_openssl.dll"
Add-Content $phpDir\php.ini "extension=$phpDir\ext\php_mbstring.dll"

Add-Content $phpDir\php.ini "extension=$phpDir\ext\php_sqlsrv.dll"
Add-Content $phpDir\php.ini "extension=$phpDir\ext\php_pdo_sqlsrv.dll"

Move-Item $phpDir\php.ini C:\Windows -force
Copy-Item $phpDir\ssleay32.dll C:\Windows -force
Copy-Item $phpDir\libeay32.dll C:\Windows -force
cd $startingDir
[Environment]::SetEnvironmentVariable("Path", $env:Path + ";" + $phpDir + ";" + $gitDir, [System.EnvironmentVariableTarget]::Machine)
$env:Path = $env:Path + ";" + $phpDir + ";" + $gitDir
RefreshEnv

# setup composer
wget https://getcomposer.org/installer -O composer-setup.php
php composer-setup.php
php composer.phar install
Remove-Item temp -Recurse -Force -ErrorAction Ignore
Write-Host "Setup completed!"
