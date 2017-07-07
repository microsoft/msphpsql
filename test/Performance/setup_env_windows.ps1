Param(
    [Parameter(Mandatory=$True,Position=1)]
    [string]$PHP_VERSION,
    [Parameter(Mandatory=$True,Position=2)]
    [string]$PHP_THREAD,
    [Parameter(Mandatory=$True,Position=3)]
    [string]$DRIVER_SOURCE_PATH,
[Parameter(Mandatory=$True,Position=4)]
    [string]$ARCH
    )

IF($ARCH -ne "x64" -And $ARCH -ne "x86"){
    Write-Host "ARCH must either x64 or x86"
    Break
}

IF($PHP_THREAD -ne "nts" -And $PHP_THREAD -ne "ts"){
    Write-Host "PHP_THREAD must either nts or ts"
    Break
}

$startingDir=$pwd.Path
$tempFolder=Join-Path $startingDir "temp"

Remove-Item temp -Recurse -Force -ErrorAction Ignore
New-Item -ItemType directory -Path temp

(New-Object System.Net.WebClient).DownloadFile("http://windows.php.net/downloads/releases/sha1sum.txt","$tempFolder\sha1sum.txt")
$PHP70_LATEST_VERSION=type $tempFolder\sha1sum.txt | where { $_ -match "php-(7.0\.\d+)-src" } | foreach { $matches[1] }
$PHP71_LATEST_VERSION=type $tempFolder\sha1sum.txt | where { $_ -match "php-(7.1\.\d+)-src" } | foreach { $matches[1] }

$PHP_VERSION_MINOR=$PHP_VERSION.split(".")[1]

Write-Host "Installing chocolatey..."
iex ((New-Object System.Net.WebClient).DownloadString('https://chocolatey.org/install.ps1'))
Write-Host "Installing Git..."
choco install -y git
Set-Alias git 'C:\Program Files\Git\cmd\git.exe'
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

Write-Host "Downloading PHP-SDK..."
(New-Object System.Net.WebClient).DownloadFile('http://windows.php.net/downloads/php-sdk/php-sdk-binary-tools-20110915.zip', "$tempFolder\binary_tools.zip")
Write-Host "Downloading PHP-$PHP_VERSION source..."
IF($PHP_VERSION -eq $PHP70_LATEST_VERSION -Or $PHP_VERSION -eq $PHP71_LATEST_VERSION){
(New-Object System.Net.WebClient).DownloadFile("http://windows.php.net/downloads/releases/php-$PHP_VERSION-src.zip", "$tempFolder\php-$PHP_VERSION-src.zip")
}
ELSE{
(New-Object System.Net.WebClient).DownloadFile("http://windows.php.net/downloads/releases/archives/php-$PHP_VERSION-src.zip", "$tempFolder\php-$PHP_VERSION-src.zip")
}

Write-Host "Downloading Dependencies..."
(New-Object System.Net.WebClient).DownloadFile("http://windows.php.net/downloads/php-sdk/deps-7.$PHP_VERSION_MINOR-vc14-$ARCH.7z", "$tempFolder\deps-7.$PHP_VERSION_MINOR-vc14-$ARCH.7z")

Add-Type -AssemblyName System.IO.Compression.FileSystem
Remove-Item C:\php-sdk -Recurse -Force -ErrorAction Ignore
New-Item -ItemType directory -Path C:\php-sdk
[System.IO.Compression.ZipFile]::ExtractToDirectory("$tempFolder\binary_tools.zip", "C:\php-sdk")
cd C:\php-sdk\
bin\phpsdk_buildtree.bat phpdev
New-Item -ItemType directory -Path .\phpdev\vc14
Copy-Item .\phpdev\vc9\* phpdev\vc14\ -recurse
[System.IO.Compression.ZipFile]::ExtractToDirectory("$tempFolder\php-$PHP_VERSION-src.zip", "C:\php-sdk\phpdev\vc14\$ARCH\")
7z.exe x $tempFolder\deps-7.$PHP_VERSION_MINOR-vc14-$ARCH.7z -oC:\php-sdk\phpdev\vc14\$ARCH\

bin\phpsdk_setvars.bat

cd C:\php-sdk\phpdev\vc14\$ARCH\php-$PHP_VERSION-src

New-Item -ItemType directory -Path .\ext\sqlsrv
New-Item -ItemType directory -Path .\ext\pdo_sqlsrv
Copy-Item $DRIVER_SOURCE_PATH\sqlsrv\* .\ext\sqlsrv\ -recurse
Copy-Item $DRIVER_SOURCE_PATH\shared\ .\ext\sqlsrv\ -recurse
Copy-Item $DRIVER_SOURCE_PATH\pdo_sqlsrv\* .\ext\pdo_sqlsrv\ -recurse
Copy-Item $DRIVER_SOURCE_PATH\shared\ .\ext\pdo_sqlsrv\ -recurse


$CONFIG_OPTIONS="--enable-cli --enable-cgi --enable-sqlsrv=shared --enable-pdo=shared   --with-pdo-sqlsrv=shared --with-odbcver=0x0380 --enable-mbstring --with-openssl"
if ($PHP_THREAD -ceq "nts") {
    $CONFIG_OPTIONS=$CONFIG_OPTIONS + " --disable-zts"
}
& $startingDir\compile_php.bat $ARCH $CONFIG_OPTIONS




Copy-Item php.ini-production php.ini
Add-Content php.ini "extension=C:\php\ext\php_sqlsrv.dll"
Add-Content php.ini "extension=C:\php\ext\php_pdo_sqlsrv.dll"
Add-Content php.ini "extension=C:\php\ext\php_openssl.dll"
Move-Item php.ini C:\Windows -force
Copy-Item C:\php-sdk\phpdev\vc14\$ARCH\deps\bin\ssleay32.dll C:\Windows -force
Copy-Item C:\php-sdk\phpdev\vc14\$ARCH\deps\bin\libeay32.dll C:\Windows -force

cd $startingDir
$env:Path += ";C:\php\"
RefreshEnv
wget https://getcomposer.org/installer -O composer-setup.php
php composer-setup.php
php composer.phar install
Remove-Item temp -Recurse -Force -ErrorAction Ignore
Write-Host "Setup completed!"