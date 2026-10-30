Param(
    [Parameter(Mandatory=$True,Position=1)]
	[string]$PHP_ZIP,
	[Parameter(Mandatory=$True,Position=2)]
    [string]$SQLSRV_DRIVER,
    [Parameter(Mandatory=$True,Position=3)]
    [string]$PDO_DRIVER
    )

$ErrorActionPreference = "Stop"

if ($PSVersionTable.PSEdition -eq 'Core') {
    throw "Run this setup in Windows PowerShell, not PowerShell Core."
}

function Assert-TrustedSetupItem {
    param($Item, [switch]$Ancestor, [switch]$CheckFileCreation)

    if ($Item.Attributes -band [IO.FileAttributes]::ReparsePoint) {
        throw "Reparse points are not supported for setup tools: $($Item.FullName)"
    }

    $acl = Get-Acl -LiteralPath $Item.FullName -ErrorAction Stop
    $descriptor = New-Object Security.AccessControl.RawSecurityDescriptor -ArgumentList @($acl.GetSecurityDescriptorBinaryForm(), 0)
    if ($null -eq $descriptor.DiscretionaryAcl) {
        throw "Setup tool paths must have a discretionary access control list: $($Item.FullName)"
    }
    $trustedOwners = @(
        'S-1-5-18', # SYSTEM
        'S-1-5-32-544', # Administrators
        'S-1-5-80-956008885-3418522649-1831038044-1853292631-2271478464' # TrustedInstaller
    )
    if ($acl.GetOwner([Security.Principal.SecurityIdentifier]).Value -notin $trustedOwners) {
        throw "Setup tool paths must be owned by Administrators, SYSTEM or TrustedInstaller: $($Item.FullName)"
    }

    $writeRights = [int64][Security.AccessControl.FileSystemRights]::Delete -bor
        [int64][Security.AccessControl.FileSystemRights]::DeleteSubdirectoriesAndFiles -bor
        [int64][Security.AccessControl.FileSystemRights]::ChangePermissions -bor
        [int64][Security.AccessControl.FileSystemRights]::TakeOwnership -bor
        0x10000000 # GENERIC_ALL
    if (-not $Ancestor) {
        # Ancestor write/create rights alone do not permit replacing an existing protected child.
        $writeRights = $writeRights -bor [int64][Security.AccessControl.FileSystemRights]::Write -bor 0x40000000
    }
    if ($CheckFileCreation) {
        $writeRights = $writeRights -bor [int64][Security.AccessControl.FileSystemRights]::WriteData -bor 0x40000000
    }

    foreach ($rule in $acl.GetAccessRules($true, $true, [Security.Principal.SecurityIdentifier])) {
        if ($rule.AccessControlType -eq [Security.AccessControl.AccessControlType]::Allow -and
            -not ($rule.PropagationFlags -band [Security.AccessControl.PropagationFlags]::InheritOnly) -and
            $rule.IdentityReference.Value -notin $trustedOwners -and
            ([int64]$rule.FileSystemRights -band $writeRights)) {
            throw "Setup tool paths must not grant write or replacement rights to non-administrators: $($Item.FullName)"
        }
    }
}

function Assert-TrustedSetupPath {
    param([string]$Path, [switch]$Recurse, [switch]$Ancestor)

    if ($Path -notmatch '^[A-Za-z]:\\' -or [Management.Automation.WildcardPattern]::ContainsWildcardCharacters($Path)) {
        throw "Setup requires an absolute local path without wildcards: $Path"
    }
    $item = Get-Item -LiteralPath $Path -Force -ErrorAction Stop
    Assert-TrustedSetupItem $item -Ancestor:$Ancestor
    $parent = Split-Path -Path $item.FullName -Parent
    while ($parent) {
        Assert-TrustedSetupItem (Get-Item -LiteralPath $parent -Force -ErrorAction Stop) -Ancestor
        $parent = Split-Path -Path $parent -Parent
    }

    if ($Recurse -and $item.PSIsContainer) {
        $directories = New-Object 'System.Collections.Generic.Stack[string]'
        $directories.Push($item.FullName)
        while ($directories.Count) {
            foreach ($child in Get-ChildItem -LiteralPath $directories.Pop() -Force -ErrorAction Stop) {
                Assert-TrustedSetupItem $child
                if ($child.PSIsContainer) {
                    $directories.Push($child.FullName)
                }
            }
        }
    }
}

function Invoke-SetupCommand {
    param([string]$Executable, [string[]]$Arguments, [int[]]$SuccessExitCodes = @(0))

    Assert-TrustedSetupPath $Executable
    Assert-TrustedSetupPath (Split-Path -Path $Executable -Parent)
    # A pipeline also waits for GUI installers in Windows PowerShell.
    & $Executable @Arguments | Write-Output
    if ($LASTEXITCODE -notin $SuccessExitCodes) {
        throw "$Executable failed with exit code $LASTEXITCODE."
    }
}

function Invoke-SetupPip {
    param([string]$Executable, [string[]]$Arguments)

    $previousConfig = [Environment]::GetEnvironmentVariable('PIP_CONFIG_FILE', 'Process')
    try {
        # Python -I isolates imports; pip separately requires os.devnull to disable config files.
        $env:PIP_CONFIG_FILE = 'nul'
        Assert-SetupPythonStartup $Executable
        Invoke-SetupCommand $Executable (@('-I', '-m', 'pip') + $Arguments)
    }
    finally {
        [Environment]::SetEnvironmentVariable('PIP_CONFIG_FILE', $previousConfig, 'Process')
    }
}

function Assert-SetupPythonStartup {
    param([string]$Executable)

    $directory = Split-Path -Path $Executable -Parent
    $parent = Split-Path -Path $directory -Parent
    if (-not $parent) { $parent = $directory }
    foreach ($location in @($directory, $parent)) {
        $config = Join-Path $location 'pyvenv.cfg'
        if (Test-Path -LiteralPath $config -ErrorAction Stop) {
            throw "Setup requires a standalone Python installation without virtual-environment configuration: $config"
        }
    }
    # -I still detects parent pyvenv.cfg. Prevent creating that file, not unrelated sibling directories.
    Assert-TrustedSetupItem (Get-Item -LiteralPath $parent -Force -ErrorAction Stop) -Ancestor -CheckFileCreation
}

function Open-SetupPythonRegistry {
    param([string]$Version)

    # Match Chocolatey's default architecture, including from 32-bit PowerShell.
    $view = if ([Environment]::Is64BitOperatingSystem) { 'Registry64' } else { 'Registry32' }
    if (-not [Environment]::Is64BitOperatingSystem) { $Version += '-32' }
    $hive = [Microsoft.Win32.RegistryKey]::OpenBaseKey([Microsoft.Win32.RegistryHive]::LocalMachine, $View)
    try {
        return $hive.OpenSubKey("SOFTWARE\Python\PythonCore\$Version\InstallPath")
    }
    finally {
        $hive.Dispose()
    }
}

function Get-SetupPython {
    param([string]$ChocolateyDirectory)

    $manifest = Join-Path $ChocolateyDirectory 'lib\python3\python3.nuspec'
    Assert-TrustedSetupPath $manifest
    $package = [xml](Get-Content -LiteralPath $manifest -Raw -ErrorAction Stop)
    if ($package.package.metadata.id -ne 'python3' -or $package.package.metadata.version -notmatch '^(3\.\d+)\.\d+') {
        throw "Cannot determine the installed Python version from $manifest"
    }
    $version = $Matches[1]
    $installPath = Open-SetupPythonRegistry $version
    if ($null -eq $installPath) {
        throw "Python $version has no machine-wide installation registered after Chocolatey completed."
    }
    try { $pythonDir = $installPath.GetValue('') }
    finally { $installPath.Dispose() }
    if ([string]::IsNullOrWhiteSpace($pythonDir)) {
        throw "Python $version has no registered installation directory."
    }
    Assert-TrustedSetupPath $pythonDir -Recurse
    $pythonExe = Join-Path $pythonDir 'python.exe'
    if (-not (Test-Path -LiteralPath $pythonExe -PathType Leaf)) {
        throw "The registered Python interpreter is missing: $pythonExe"
    }
    return $pythonExe
}

function New-SetupDirectoryAcl {
    $acl = New-Object Security.AccessControl.DirectorySecurity
    $admins = New-Object Security.Principal.SecurityIdentifier 'S-1-5-32-544'
    $acl.SetOwner($admins)
    $acl.SetAccessRuleProtection($true, $false)
    foreach ($sid in @('S-1-5-32-544', 'S-1-5-18', 'S-1-5-32-545')) {
        $identity = New-Object Security.Principal.SecurityIdentifier $sid
        $rights = if ($sid -eq 'S-1-5-32-545') { 'ReadAndExecute' } else { 'FullControl' }
        $rule = New-Object Security.AccessControl.FileSystemAccessRule(
            $identity, $rights, 'ContainerInherit, ObjectInherit', 'None', 'Allow')
        $acl.AddAccessRule($rule)
    }
    return $acl
}

function New-SetupPhpDirectory {
    param([string]$Path)

    Assert-TrustedSetupPath (Split-Path -Path $Path -Parent) -Ancestor
    if (Test-Path -LiteralPath $Path) {
        if (-not (Test-Path -LiteralPath $Path -PathType Container)) {
            throw "Expected a PHP installation directory: $Path"
        }
        Assert-TrustedSetupPath $Path -Recurse
        Remove-Item -LiteralPath $Path -Recurse -Force -ErrorAction Stop
        if (Test-Path -LiteralPath $Path) {
            throw "Could not remove the previous PHP installation: $Path"
        }
    }

    $acl = New-SetupDirectoryAcl
    # Apply the DACL at creation, not after creating a directory with inherited root permissions.
    [IO.Directory]::CreateDirectory($Path, $acl) | Out-Null
    Assert-TrustedSetupPath $Path -Recurse
}

function Add-SetupPath {
    param([AllowEmptyString()][string]$Path, [string[]]$Directories)

    foreach ($directory in $Directories) {
        $entries = @($Path -split ';' | ForEach-Object { $_.Trim().TrimEnd('\') })
        if ($directory.TrimEnd('\') -notin $entries) {
            if ($Path -and -not $Path.EndsWith(';')) {
                $Path += ';'
            }
            $Path += $directory
        }
    }
    return $Path
}

function Open-SetupEnvironmentRegistry {
    return [Microsoft.Win32.Registry]::LocalMachine.OpenSubKey(
        'SYSTEM\CurrentControlSet\Control\Session Manager\Environment', $true)
}

function Send-SetupEnvironmentChange {
    if (-not ('SetupEnvironmentNotification' -as [type])) {
        Add-Type -TypeDefinition @'
using System;
using System.Runtime.InteropServices;
public static class SetupEnvironmentNotification
{
    [DllImport("user32.dll", CharSet = CharSet.Unicode, SetLastError = true)]
    public static extern IntPtr SendMessageTimeout(
        IntPtr window, uint message, UIntPtr wParam, string lParam,
        uint flags, uint timeout, out UIntPtr result);
}
'@
    }
    $result = [UIntPtr]::Zero
    $sent = [SetupEnvironmentNotification]::SendMessageTimeout(
        [IntPtr]0xffff, 0x001a, [UIntPtr]::Zero, 'Environment', 2, 5000, [ref]$result)
    if ($sent -eq [IntPtr]::Zero) {
        throw "Machine PATH was saved, but the environment-change notification failed (Win32 error $([Runtime.InteropServices.Marshal]::GetLastWin32Error()))."
    }
}

function Add-SetupMachinePath {
    param([string[]]$Directories)

    $key = Open-SetupEnvironmentRegistry
    if ($null -eq $key) {
        throw 'Cannot open the machine environment registry key for writing.'
    }
    try {
        $path = $key.GetValue('Path', $null, [Microsoft.Win32.RegistryValueOptions]::DoNotExpandEnvironmentNames)
        $kind = [Microsoft.Win32.RegistryValueKind]::ExpandString
        if ($null -ne $path) {
            $kind = $key.GetValueKind('Path')
            if ($kind -notin @([Microsoft.Win32.RegistryValueKind]::String, [Microsoft.Win32.RegistryValueKind]::ExpandString)) {
                throw "Machine PATH must be a string registry value, not $kind."
            }
        }
        $key.SetValue('Path', (Add-SetupPath $path $Directories), $kind)
    }
    finally {
        $key.Dispose()
    }
    Send-SetupEnvironmentChange
}

$startingDir=$pwd.Path
$tempFolder=Join-Path $startingDir "temp"

Remove-Item temp -Recurse -Force -ErrorAction Ignore
New-Item -ItemType directory -Path temp

Write-Host "Installing chocolatey..."
iex ((New-Object System.Net.WebClient).DownloadString('https://chocolatey.org/install.ps1'))
$chocoDir = $env:ChocolateyInstall
$chocoExe = Join-Path $chocoDir 'choco.exe'
Write-Host "Installing Git..."
Invoke-SetupCommand $chocoExe @('install', '-y', 'git') @(0, 1641, 3010)

$gitDir = 'C:\Program Files\Git\cmd'
Write-Host "Installing Python3..."
Invoke-SetupCommand $chocoExe @('install', '-y', 'python3') @(0, 1641, 3010)
RefreshEnv
$pythonExe = Get-SetupPython $chocoDir
Write-Host "Installing pyodbc..."
Invoke-SetupPip $pythonExe @('install', 'pyodbc') | Out-Null
Write-Host "Downloading MSODBCSQL..."
#This needs to be manually updated when there is a new release
(New-object System.Net.WebClient).DownloadFile('https://download.microsoft.com/download/D/5/E/D5EEF288-A277-45C8-855B-8E2CB7E25B96/x64/msodbcsql.msi', "$tempFolder\msodbcsql.msi")
Write-Host "Installing MSODBCSQL..."
msiexec /quiet /passive /qn /i $tempFolder\msodbcsql.msi IACCEPTMSODBCSQLLICENSETERMS=YES | Out-Null
Write-Host "Installing 7-Zip..."
Invoke-SetupCommand $chocoExe @('install', '-y', '7zip.install') @(0, 1641, 3010)

Write-Host "Installing PHP..."
$phpDir="C:\php"

# remove existing PHP and setup new one
New-SetupPhpDirectory $phpDir
Expand-Archive $PHP_ZIP -DestinationPath $phpDir
Assert-TrustedSetupPath $phpDir -Recurse

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
Assert-TrustedSetupPath $phpDir -Recurse
Assert-TrustedSetupPath $gitDir -Recurse
$phpExe = Join-Path $phpDir 'php.exe'
foreach ($executable in @($phpExe, (Join-Path $gitDir 'git.exe'))) {
    if (-not (Test-Path -LiteralPath $executable -PathType Leaf)) {
        throw "A required setup executable is missing: $executable"
    }
}
cd $startingDir
Add-SetupMachinePath @($phpDir, $gitDir)
$env:Path = Add-SetupPath $env:Path @($phpDir, $gitDir)
RefreshEnv

# setup composer
wget https://getcomposer.org/installer -O composer-setup.php
Invoke-SetupCommand $phpExe @('composer-setup.php')
Invoke-SetupCommand $phpExe @('composer.phar', 'install')
Remove-Item temp -Recurse -Force -ErrorAction Ignore
Write-Host "Setup completed!"
