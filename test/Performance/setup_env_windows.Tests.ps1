$ErrorActionPreference = 'Stop'
$setupFile = Join-Path $PSScriptRoot 'setup_env_windows.ps1'
$tokens = $null
$parseErrors = $null
$setupAst = [Management.Automation.Language.Parser]::ParseFile($setupFile, [ref]$tokens, [ref]$parseErrors)
if ($parseErrors.Count) {
    throw ($parseErrors | Out-String)
}

# Load only function definitions. Never dot-source or execute the provisioning script.
foreach ($definition in $setupAst.EndBlock.Statements) {
    if ($definition -is [Management.Automation.Language.FunctionDefinitionAst]) {
        . ([scriptblock]::Create($definition.Extent.Text))
    }
}

Describe 'Windows setup native exit handling' {
    $commandProcessor = Join-Path $env:SystemRoot 'System32\cmd.exe'
    Mock Assert-TrustedSetupPath {}

    It 'accepts a successful native command' {
        { Invoke-SetupCommand $commandProcessor @('/d', '/c', 'exit', '/b', '0') } | Should Not Throw
    }

    It 'throws on native failure even with ErrorActionPreference Stop' {
        { Invoke-SetupCommand $commandProcessor @('/d', '/c', 'exit', '/b', '17') } | Should Throw 'exit code 17'
    }

    It 'stops the caller before any subsequent setup step' {
        $continued = $false
        try {
            Invoke-SetupCommand $commandProcessor @('/d', '/c', 'exit', '/b', '1')
            $continued = $true
        }
        catch {
            $_.Exception.Message | Should Match 'exit code 1'
        }
        $continued | Should Be $false
    }

    It 'accepts the explicitly allowed installer reboot codes' {
        foreach ($code in @(1641, 3010)) {
            { Invoke-SetupCommand $commandProcessor @('/d', '/c', 'exit', '/b', "$code") @(0, 1641, 3010) } | Should Not Throw
        }
    }

    It 'does not accept reboot codes from pip or Composer by default' {
        { Invoke-SetupCommand $commandProcessor @('/d', '/c', 'exit', '/b', '3010') } | Should Throw 'exit code 3010'
    }

    It 'preserves native output' {
        (Invoke-SetupCommand $commandProcessor @('/d', '/c', 'echo setup-output')) | Should Be 'setup-output'
    }

    It 'rejects an untrusted executable before invocation' {
        Mock Assert-TrustedSetupPath { throw 'Unsafe executable' }
        { Invoke-SetupCommand 'C:\NotExecuted.exe' @() } | Should Throw 'Unsafe executable'
    }
}

Describe 'Machine-wide Python selection' {
    BeforeEach {
        $script:registrations = @{
            Registry64 = @{ '3.12' = 'C:\Python312\' }
            Registry32 = @{}
        }
    }
    Mock Test-Path { $true }
    Mock Open-SetupPythonRegistry {
        $root = [pscustomobject]@{ Entries = $script:registrations[$View.ToString()] }
        $root | Add-Member ScriptMethod GetSubKeyNames { $this.Entries.Keys }
        $root | Add-Member ScriptMethod OpenSubKey {
            param($Name)
            $key = [pscustomobject]@{ Directory = $this.Entries[$Name.Split('\')[0]] }
            $key | Add-Member ScriptMethod GetValue { param($Name) $this.Directory }
            $key | Add-Member ScriptMethod Dispose {}
            $key
        }
        $root | Add-Member ScriptMethod Dispose {}
        $root
    }
    Mock Assert-TrustedSetupPath {}

    It 'returns the registered interpreter, not pip or a PATH match' {
        Get-SetupPython | Should Be 'C:\Python312\python.exe'
        Assert-MockCalled Assert-TrustedSetupPath -Times 1 -Exactly -Scope It -ParameterFilter {
            $Path -eq 'C:\Python312' -and $Recurse
        }
    }

    It 'accepts a unique trusted installation outside the former wildcard' {
        $script:registrations.Registry64['3.12'] = 'D:\Tools\Python'
        Get-SetupPython | Should Be 'D:\Tools\Python\python.exe'
    }

    It 'reads both registry views and accepts a 32-bit installation' {
        $script:registrations.Registry64 = @{}
        $script:registrations.Registry32 = @{ '3.12-32' = 'C:\Python312-32' }
        Get-SetupPython | Should Be 'C:\Python312-32\python.exe'
        Assert-MockCalled Open-SetupPythonRegistry -Times 1 -Exactly -Scope It -ParameterFilter { $View -eq 'Registry32' }
        Assert-MockCalled Open-SetupPythonRegistry -Times 1 -Exactly -Scope It -ParameterFilter { $View -eq 'Registry64' }
    }

    It 'deduplicates the same installation registered in both views' {
        $script:registrations.Registry32 = @{ '3.12' = 'C:\Python312' }
        Get-SetupPython | Should Be 'C:\Python312\python.exe'
    }

    It 'rejects missing machine-wide installations' {
        Mock Open-SetupPythonRegistry { $null }
        { Get-SetupPython } | Should Throw 'found 0'
        Assert-MockCalled Assert-TrustedSetupPath -Times 0 -Exactly -Scope It
    }

    It 'rejects multiple distinct installations rather than choosing one' {
        $script:registrations.Registry64['3.13'] = 'C:\Python313'
        { Get-SetupPython } | Should Throw 'found 2'
        Assert-MockCalled Assert-TrustedSetupPath -Times 0 -Exactly -Scope It
    }

    It 'ignores Python 2 registrations' {
        $script:registrations.Registry64 = @{ '2.7' = 'C:\Python27' }
        { Get-SetupPython } | Should Throw 'found 0'
    }

    It 'rejects an incomplete registration' {
        $script:registrations.Registry64['3.12'] = ''
        { Get-SetupPython } | Should Throw 'no registered installation directory'
    }

    It 'propagates registry access errors' {
        Mock Open-SetupPythonRegistry { throw 'Registry access denied' }
        { Get-SetupPython } | Should Throw 'Registry access denied'
    }

    It 'rejects a missing interpreter instead of falling back to PATH' {
        Mock Test-Path { $PathType -ne 'Leaf' }
        { Get-SetupPython } | Should Throw 'registered Python interpreter is missing'
    }

    It 'requires the entire registered installation to be trusted' {
        Mock Assert-TrustedSetupPath { throw 'Untrusted installation' }
        { Get-SetupPython } | Should Throw 'Untrusted installation'
    }
}

Describe 'Setup directory security descriptors' {
    BeforeEach {
        $script:acl = New-SetupDirectoryAcl
        $script:item = [pscustomobject]@{ FullName = 'C:\php'; Attributes = [IO.FileAttributes]::Directory }
    }
    Mock Get-Acl { $script:acl }

    It 'creates an administrator-owned protected DACL with inheritable read/execute for users' {
        $acl.GetOwner([Security.Principal.SecurityIdentifier]).Value | Should Be 'S-1-5-32-544'
        $acl.AreAccessRulesProtected | Should Be $true
        $rules = $acl.GetAccessRules($true, $true, [Security.Principal.SecurityIdentifier])
        $rules.Count | Should Be 3
        $users = $rules | Where-Object { $_.IdentityReference.Value -eq 'S-1-5-32-545' }
        ($users.FileSystemRights -band [Security.AccessControl.FileSystemRights]::Write) | Should Be 0
        $users.InheritanceFlags.ToString() | Should Be 'ContainerInherit, ObjectInherit'
        { Assert-TrustedSetupItem $item } | Should Not Throw
    }

    It 'rejects a non-administrator owner even with a protected DACL' {
        $acl.SetOwner((New-Object Security.Principal.SecurityIdentifier 'S-1-5-21-1-2-3-1001'))
        { Assert-TrustedSetupItem $item } | Should Throw 'must be owned'
    }

    It 'rejects a null DACL' {
        $acl.SetSecurityDescriptorSddlForm('O:BAD:NO_ACCESS_CONTROL')
        { Assert-TrustedSetupItem $item } | Should Throw 'must have a discretionary'
    }

    foreach ($rights in @('Modify', 'WriteData', 'AppendData', 'WriteAttributes', 'Delete', 'DeleteSubdirectoriesAndFiles', 'ChangePermissions', 'TakeOwnership')) {
        It "rejects non-administrator $rights access" {
            $rule = New-Object Security.AccessControl.FileSystemAccessRule(
                (New-Object Security.Principal.SecurityIdentifier 'S-1-1-0'), $rights, 'Allow')
            $acl.AddAccessRule($rule)
            { Assert-TrustedSetupItem $item } | Should Throw 'must not grant write or replacement rights'
        }
    }

    It 'rejects unmapped generic write rights' {
        $acl.SetSecurityDescriptorSddlForm('O:BAD:(A;;GA;;;BA)(A;;GW;;;BU)')
        { Assert-TrustedSetupItem $item } | Should Throw 'must not grant write or replacement rights'
    }

    It 'does not mistake volume-root child creation for replacement rights' {
        $item.FullName = 'C:\'
        $acl.SetSecurityDescriptorSddlForm('O:BAD:(A;;FA;;;BA)(A;;0x4;;;AU)')
        { Assert-TrustedSetupItem $item } | Should Not Throw
    }

    It 'still rejects volume-root delete-child rights' {
        $item.FullName = 'C:\'
        $acl.SetSecurityDescriptorSddlForm('O:BAD:(A;;FA;;;BA)(A;;0x40;;;AU)')
        { Assert-TrustedSetupItem $item } | Should Throw 'must not grant write or replacement rights'
    }

    It 'does not treat inherit-only rules as effective on the parent' {
        $acl.SetSecurityDescriptorSddlForm('O:BAD:(A;;FA;;;BA)(A;OICIIO;GA;;;CO)')
        { Assert-TrustedSetupItem $item } | Should Not Throw
    }

    It 'rejects reparse points before reading their ACL' {
        $item.Attributes = [IO.FileAttributes]::ReparsePoint
        { Assert-TrustedSetupItem $item } | Should Throw 'Reparse points'
        Assert-MockCalled Get-Acl -Times 0 -Exactly -Scope It
    }
}

Describe 'Setup path and descendant validation' {
    Mock Get-Item {
        [pscustomobject]@{ FullName = $LiteralPath; Attributes = [IO.FileAttributes]::Directory; PSIsContainer = $true }
    }
    Mock Get-Acl { New-SetupDirectoryAcl }
    Mock Get-ChildItem {
        if ($LiteralPath -eq 'C:\php') {
            [pscustomobject]@{ FullName = 'C:\php\ext'; Attributes = [IO.FileAttributes]::Directory; PSIsContainer = $true }
        }
        elseif ($LiteralPath -eq 'C:\php\ext') {
            [pscustomobject]@{ FullName = 'C:\php\ext\php_sqlsrv.dll'; Attributes = [IO.FileAttributes]::Normal; PSIsContainer = $false }
        }
    }

    It 'checks ancestors and extension descendants' {
        { Assert-TrustedSetupPath 'C:\php' -Recurse } | Should Not Throw
        Assert-MockCalled Get-Acl -Times 1 -Exactly -Scope It -ParameterFilter { $LiteralPath -eq 'C:\' }
        Assert-MockCalled Get-Acl -Times 1 -Exactly -Scope It -ParameterFilter { $LiteralPath -eq 'C:\php\ext\php_sqlsrv.dll' }
    }

    It 'rejects relative, UNC and wildcard paths before accessing them' {
        foreach ($path in @('php', '\\server\share\php', 'C:\Python*', 'C:php')) {
            { Assert-TrustedSetupPath $path } | Should Throw 'absolute local path without wildcards'
        }
        Assert-MockCalled Get-Item -Times 0 -Exactly -Scope It
    }

    It 'rejects mapped network drives before trusting remote ACLs' {
        Mock New-Object { [pscustomobject]@{ DriveType = [IO.DriveType]::Network } } -ParameterFilter { $TypeName -eq 'IO.DriveInfo' }
        { Assert-TrustedSetupPath 'C:\php' -Recurse } | Should Throw 'local fixed drive'
        Assert-MockCalled Get-Item -Times 0 -Exactly -Scope It
    }

    It 'rejects an unsafe executable or extension even when its directory is protected' {
        Mock Get-Acl {
            $acl = New-SetupDirectoryAcl
            if ($LiteralPath -eq 'C:\php\ext\php_sqlsrv.dll') {
                $acl.SetSecurityDescriptorSddlForm('O:BAD:(A;;FA;;;BA)(A;;FW;;;BU)')
            }
            $acl
        }
        { Assert-TrustedSetupPath 'C:\php' -Recurse } | Should Throw 'must not grant write or replacement rights'
    }

    It 'rejects unsafe ancestor replacement rights' {
        Mock Get-Acl {
            $acl = New-SetupDirectoryAcl
            if ($LiteralPath -eq 'C:\') {
                $acl.SetSecurityDescriptorSddlForm('O:BAD:(A;;FA;;;BA)(A;;0x40;;;AU)')
            }
            $acl
        }
        { Assert-TrustedSetupPath 'C:\php' -Recurse } | Should Throw 'must not grant write or replacement rights'
        Assert-MockCalled Get-ChildItem -Times 0 -Exactly -Scope It
    }

    It 'rejects descendant junctions without enumerating through them' {
        Mock Get-ChildItem {
            [pscustomobject]@{ FullName = 'C:\php\ext'; Attributes = [IO.FileAttributes]::ReparsePoint; PSIsContainer = $true }
        }
        { Assert-TrustedSetupPath 'C:\php' -Recurse } | Should Throw 'Reparse points'
        Assert-MockCalled Get-ChildItem -Times 0 -Exactly -Scope It -ParameterFilter { $LiteralPath -eq 'C:\php\ext' }
    }

    It 'propagates access errors rather than treating inaccessible files as safe' {
        Mock Get-Acl { throw 'Access denied' }
        { Assert-TrustedSetupPath 'C:\php' -Recurse } | Should Throw 'Access denied'
    }
}

Describe 'PHP replacement failure handling' {
    Mock Test-Path { $true }
    Mock Assert-TrustedSetupPath {}
    Mock Remove-Item {}
    # These cases must stop before the real Directory.CreateDirectory call.
    Mock New-SetupDirectoryAcl { throw 'Unexpected directory creation' }

    It 'refuses to remove an untrusted existing installation' {
        Mock Assert-TrustedSetupPath {
            if ($Path -eq 'C:\php') { throw 'Unsafe existing PHP installation' }
        }
        { New-SetupPhpDirectory 'C:\php' } | Should Throw 'Unsafe existing PHP installation'
        Assert-MockCalled Remove-Item -Times 0 -Exactly -Scope It
    }

    It 'rejects an unexpected file at the installation path' {
        Mock Test-Path { $PathType -ne 'Container' }
        { New-SetupPhpDirectory 'C:\php' } | Should Throw 'Expected a PHP installation directory'
        Assert-MockCalled Remove-Item -Times 0 -Exactly -Scope It
    }

    It 'does not swallow deletion errors' {
        Mock Remove-Item { throw 'PHP directory is in use' }
        { New-SetupPhpDirectory 'C:\php' } | Should Throw 'PHP directory is in use'
        Assert-MockCalled New-SetupDirectoryAcl -Times 0 -Exactly -Scope It
    }

    It 'fails if the previous path remains after cleanup' {
        { New-SetupPhpDirectory 'C:\php' } | Should Throw 'Could not remove the previous PHP installation'
        Assert-MockCalled New-SetupDirectoryAcl -Times 0 -Exactly -Scope It
    }
}

Describe 'Persistent benchmark PATH composition' {
    $directories = @('C:\php', 'C:\Program Files\Git\cmd')

    It 'preserves existing machine entries and their order' {
        Add-SetupPath '%SystemRoot%\System32;C:\Tools' $directories |
            Should Be '%SystemRoot%\System32;C:\Tools;C:\php;C:\Program Files\Git\cmd'
    }

    It 'does not import process or user entries' {
        Add-SetupPath 'C:\MachineTools' $directories | Should Be 'C:\MachineTools;C:\php;C:\Program Files\Git\cmd'
    }

    It 'does not duplicate existing entries on a repeated run' {
        $original = 'C:\Tools;c:\PHP\;C:\Program Files\Git\cmd'
        Add-SetupPath $original $directories | Should Be $original
    }

    It 'supports an empty machine PATH without adding a current-directory entry' {
        Add-SetupPath '' $directories | Should Be 'C:\php;C:\Program Files\Git\cmd'
    }

    It 'preserves a trailing separator without adding another one' {
        Add-SetupPath 'C:\Tools;' $directories | Should Be 'C:\Tools;C:\php;C:\Program Files\Git\cmd'
    }
}

Describe 'Provisioning wiring (static only)' {
    $source = $setupAst.Extent.Text

    It 'uses isolated module lookup with the selected Python interpreter' {
        $source | Should Match ([regex]::Escape("Invoke-SetupCommand `$pythonExe @('-I', '-m', 'pip', 'install', 'pyodbc')"))
        $source | Should Not Match ([regex]::Escape('C:\Python*\Scripts\pip3.exe'))
    }

    It 'uses fully qualified PHP for both Composer steps' {
        $source | Should Match ([regex]::Escape("Invoke-SetupCommand `$phpExe @('composer-setup.php')"))
        $source | Should Match ([regex]::Escape("Invoke-SetupCommand `$phpExe @('composer.phar', 'install')"))
    }

    It 'persists machine PATH plus only the PHP and Git directories' {
        $source | Should Match ([regex]::Escape('$machinePath = [Environment]::GetEnvironmentVariable("Path", [System.EnvironmentVariableTarget]::Machine)'))
        $source | Should Match ([regex]::Escape('[Environment]::SetEnvironmentVariable("Path", (Add-SetupPath $machinePath @($phpDir, $gitDir)), [System.EnvironmentVariableTarget]::Machine)'))
        $source | Should Match ([regex]::Escape("`$gitDir = 'C:\Program Files\Git\cmd'"))
        $source | Should Match ([regex]::Escape('$env:Path = Add-SetupPath $env:Path @($phpDir, $gitDir)'))
    }

    It 'creates PHP with its protected DACL and validates it before use' {
        $source | Should Match ([regex]::Escape('[IO.Directory]::CreateDirectory($Path, $acl)'))
        $source | Should Match 'New-SetupPhpDirectory \$phpDir\s+Expand-Archive.*\r?\nAssert-TrustedSetupPath \$phpDir -Recurse'
        $source.LastIndexOf('Assert-TrustedSetupPath $phpDir -Recurse') |
            Should BeLessThan $source.IndexOf('[Environment]::SetEnvironmentVariable')
    }

    It 'keeps installer failures on the checked native-command path' {
        foreach ($package in @('git', 'python3', '7zip.install')) {
            $source | Should Match ([regex]::Escape("Invoke-SetupCommand `$chocoExe @('install', '-y', '$package') @(0, 1641, 3010)"))
        }
        $source | Should Match ([regex]::Escape('Invoke-SetupCommand "$env:SystemRoot\System32\msiexec.exe"'))
    }
}
