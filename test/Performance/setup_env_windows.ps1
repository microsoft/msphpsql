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

function Get-UrlStatusCode([string] $Url)
{
    try
    {
        (Invoke-WebRequest -Uri $Url -UseBasicParsing -DisableKeepAlive).StatusCode
    }
    catch [Net.WebException]
    {
        [int]$_.Exception.Response.StatusCode
    }
}

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

(New-Object System.Net.WebClient).DownloadFile('http://windows.php.net/downloads/releases/sha1sum.txt',"$tempFolder\sha1sum.txt")
$PHP70_LATEST_VERSION=type $tempFolder\sha1sum.txt | where { $_ -match "php-(7.0\.\d+)-src" } | foreach { $matches[1] }
$PHP71_LATEST_VERSION=type $tempFolder\sha1sum.txt | where { $_ -match "php-(7.1\.\d+)-src" } | foreach { $matches[1] }
#check if PHP_VERSION source exisits
$statusCode = Get-UrlStatusCode "http://windows.php.net/downloads/releases/php-$PHP_VERSION-src.zip"
#If provided php version source does not exists, default the version to latest php 71 source
IF($statusCode -eq 404){$PHP_VERSION=$PHP71_LATEST_VERSION}
$PHP_VERSION_MINOR=$PHP_VERSION.split(".")[1]

Write-Host "Installing chocolatey..."
iex ((New-Object System.Net.WebClient).DownloadString('https://chocolatey.org/install.ps1'))
Write-Host "Installing Git..."
choco install git
Write-Host "Downloading Python3..."
(New-Object System.Net.WebClient).DownloadFile('https://www.python.org/ftp/python/3.6.0/python-3.6.0-amd64.exe', "$tempFolder\python.exe")
Write-Host "Installing Python3..."
.\temp\python.exe /quiet InstallAllUsers=1 PrependPath=1 Include_test=0 | Out-Null
Write-Host "Installing pyodbc..."
pip3 install pyodbc | Out-Null
Write-Host "Downloading MSODBCSQL..."
(New-object System.Net.WebClient).DownloadFile('https://download.microsoft.com/download/D/5/E/D5EEF288-A277-45C8-855B-8E2CB7E25B96/x64/msodbcsql.msi', "$tempFolder\msodbcsql.msi")
Write-Host "Installing MSODBCSQL..."
msiexec /quiet /passive /qn /i $tempFolder\msodbcsql.msi IACCEPTMSODBCSQLLICENSETERMS=YES | Out-Null
Write-Host "Downloading 7-Zip..."
(New-object System.Net.WebClient).DownloadFile("http://www.7-zip.org/a/7z1604-x64.exe", "$tempFolder\7z1604-x64.exe")
Write-Host "Installing 7-Zip..."
.\temp\7z1604-x64.exe /S | Out-Null
set-alias sz "$env:ProgramFiles\7-Zip\7z.exe"  
Write-Host "Downloading PHP-SDK..."
(New-Object System.Net.WebClient).DownloadFile('http://windows.php.net/downloads/php-sdk/php-sdk-binary-tools-20110915.zip', "$tempFolder\binary_tools.zip")
Write-Host "Downloading PHP-$PHP_VERSION source..."
(New-Object System.Net.WebClient).DownloadFile("http://windows.php.net/downloads/releases/php-$PHP_VERSION-src.zip", "$tempFolder\php-$PHP_VERSION-src.zip")
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
set-alias sz "$env:ProgramFiles\7-Zip\7z.exe"  
sz x $tempFolder\deps-7.$PHP_VERSION_MINOR-vc14-$ARCH.7z -oC:\php-sdk\phpdev\vc14\$ARCH\

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
set-alias php "C:\php\php.exe"
wget https://getcomposer.org/installer -O composer-setup.php
php composer-setup.php
php composer.phar install