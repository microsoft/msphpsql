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

$PHP_VERSION_MINOR=$PHP_VERSION.split(".")[1]
$startingDir=$pwd.Path
$tempFolder=Join-Path $startingDir "temp"
echo $tempFolder

Remove-Item temp -Recurse -Force -ErrorAction Ignore
New-Item -ItemType directory -Path temp

Write-Host "Downloading Git..."
(New-Object System.Net.WebClient).DownloadFile('https://github.com/git-for-windows/git/releases/download/v2.13.0.windows.1/Git-2.13.0-64-bit.exe', "$tempFolder\git.exe") 
Write-Host "Installing Git..."
.\temp\git.exe /SILENT | Out-Null
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
.\buildconf --force
$CONFIG_OPTIONS="--enable-cli --enable-cgi --enable-pdo --enable-sqlsrv=shared --with-pdo_sqlsrv=shared --with-odbcver=0x0380 --with-zlib --enable-mbstring"
if ($PHP_THREAD -ceq "nts") {
    $CONFIG_OPTIONS=$CONFIG_OPTIONS + "--disable-zts"
}
.\configure $CONFIG_OPTIONS
nmake


