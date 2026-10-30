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

    It 'checks the immediate executable directory as a target without recursion' {
        Invoke-SetupCommand $commandProcessor @('/d', '/c', 'exit', '/b', '0')
        Assert-MockCalled Assert-TrustedSetupPath -Times 1 -Exactly -Scope It -ParameterFilter {
            $Path -eq (Split-Path $commandProcessor -Parent) -and -not $Ancestor -and -not $Recurse
        }
    }

    It 'rejects an untrusted executable before invocation' {
        Mock Assert-TrustedSetupPath { throw 'Unsafe executable' }
        { Invoke-SetupCommand 'C:\NotExecuted.exe' @() } | Should Throw 'Unsafe executable'
    }
}

Describe 'Scoped pip configuration' {
    BeforeEach {
        $script:originalPipConfig = [Environment]::GetEnvironmentVariable('PIP_CONFIG_FILE', 'Process')
        Mock Assert-SetupPythonStartup {}
        Mock Invoke-SetupCommand {
            if ($env:PIP_CONFIG_FILE -ne 'nul' -or $Executable -ne 'C:\Python312\python.exe' -or
                ($Arguments -join ' ') -ne '-I -m pip install pyodbc') {
                throw 'Unexpected pip invocation or configuration'
            }
            'pip completed'
        }
    }
    AfterEach {
        [Environment]::SetEnvironmentVariable('PIP_CONFIG_FILE', $script:originalPipConfig, 'Process')
    }

    It 'restores configuration and never invokes Python when startup validation fails' {
        $env:PIP_CONFIG_FILE = 'C:\Previous\pip.ini'
        Mock Assert-SetupPythonStartup { throw 'Unsafe Python startup configuration' }
        { Invoke-SetupPip 'C:\Python312\python.exe' @('install', 'pyodbc') } | Should Throw 'Unsafe Python startup'
        $env:PIP_CONFIG_FILE | Should Be 'C:\Previous\pip.ini'
        Assert-MockCalled Invoke-SetupCommand -Times 0 -Exactly -Scope It
    }

    foreach ($previous in @($null, 'C:\Previous\pip.ini')) {
        It "restores prior pip configuration '$previous' after success" {
            [Environment]::SetEnvironmentVariable('PIP_CONFIG_FILE', $previous, 'Process')
            Invoke-SetupPip 'C:\Python312\python.exe' @('install', 'pyodbc') | Should Be 'pip completed'
            [Environment]::GetEnvironmentVariable('PIP_CONFIG_FILE', 'Process') | Should Be $previous
            (Test-Path Env:\PIP_CONFIG_FILE) | Should Be ($null -ne $previous)
        }

        It "restores prior pip configuration '$previous' after failure" {
            [Environment]::SetEnvironmentVariable('PIP_CONFIG_FILE', $previous, 'Process')
            Mock Invoke-SetupCommand {
                $env:PIP_CONFIG_FILE | Should Be 'nul'
                throw 'pip failed with exit code 17'
            }
            { Invoke-SetupPip 'C:\Python312\python.exe' @('install', 'pyodbc') } | Should Throw 'exit code 17'
            [Environment]::GetEnvironmentVariable('PIP_CONFIG_FILE', 'Process') | Should Be $previous
            (Test-Path Env:\PIP_CONFIG_FILE) | Should Be ($null -ne $previous)
        }
    }

    It 'verifies real pip skips global user and site files with Windows os.devnull' {
        $probe = @'
import os
import sys
from unittest.mock import patch
from pip._internal import configuration

assert sys.flags.isolated and os.devnull == 'nul'
kinds = getattr(configuration, 'kinds', None) or configuration.Kind
Configuration = configuration.Configuration
files = {kinds.GLOBAL: ['global.ini'], kinds.USER: ['user.ini'], kinds.SITE: ['site.ini']}
with patch('pip._internal.configuration.get_configuration_files', return_value=files):
    with patch.dict(os.environ, {}, clear=True):
        config = Configuration(isolated=False)
        with patch.object(config, '_load_file') as load:
            config.load()
            assert load.call_count == 3, load.call_args_list
    with patch.dict(os.environ, {'PIP_CONFIG_FILE': 'nul'}, clear=True):
        config = Configuration(isolated=False)
        with patch.object(config, '_load_file') as load:
            config.load()
            assert load.call_count == 0, load.call_args_list
print('pip configuration isolation verified')
'@
        $output = & python.exe -I -c $probe
        $LASTEXITCODE | Should Be 0
        $output | Should Be 'pip configuration isolation verified'
    }
}

Describe 'Standalone Python startup trust' {
    BeforeEach {
        Mock Test-Path { $false }
        Mock Get-Item {
            [pscustomobject]@{ FullName = $LiteralPath; Attributes = [IO.FileAttributes]::Directory; PSIsContainer = $true }
        }
        Mock Get-Acl { New-SetupDirectoryAcl }
    }

    foreach ($expectedConfigPath in @('C:\Tools\Python312\pyvenv.cfg', 'C:\Tools\pyvenv.cfg')) {
        It "rejects existing virtual-environment configuration at $expectedConfigPath" {
            $script:expectedStartupConfig = $expectedConfigPath
            Mock Test-Path { $LiteralPath -eq $script:expectedStartupConfig }
            { Assert-SetupPythonStartup 'C:\Tools\Python312\python.exe' } | Should Throw $script:expectedStartupConfig
        }
    }

    It 'rejects parent file creation even when pyvenv.cfg is absent' {
        Mock Get-Acl {
            $acl = New-SetupDirectoryAcl
            $acl.SetSecurityDescriptorSddlForm('O:BAD:(A;;FA;;;BA)(A;;0x2;;;BU)')
            $acl
        }
        { Assert-SetupPythonStartup 'C:\Tools\Python312\python.exe' } |
            Should Throw 'must not grant write or replacement rights'
    }

    It 'rejects generic write rights on the configuration parent' {
        Mock Get-Acl {
            $acl = New-SetupDirectoryAcl
            $acl.SetSecurityDescriptorSddlForm('O:BAD:(A;;GA;;;BA)(A;;GW;;;BU)')
            $acl
        }
        { Assert-SetupPythonStartup 'C:\Tools\Python312\python.exe' } |
            Should Throw 'must not grant write or replacement rights'
    }

    It 'allows root sibling-directory creation without file creation' {
        Mock Get-Acl {
            $acl = New-SetupDirectoryAcl
            $acl.SetSecurityDescriptorSddlForm('O:BAD:(A;;FA;;;BA)(A;;0x4;;;BU)')
            $acl
        }
        { Assert-SetupPythonStartup 'C:\Python312\python.exe' } | Should Not Throw
        Assert-MockCalled Get-Acl -Times 1 -Exactly -Scope It -ParameterFilter { $LiteralPath -eq 'C:\' }
    }

    It 'keeps alternate protected standalone installation layouts supported' {
        { Assert-SetupPythonStartup 'D:\Tools\Python\python.exe' } | Should Not Throw
        Assert-MockCalled Get-Acl -Times 1 -Exactly -Scope It -ParameterFilter { $LiteralPath -eq 'D:\Tools' }
    }

    It 'does not treat inaccessible configuration as absent' {
        Mock Test-Path { throw 'Configuration access denied' }
        { Assert-SetupPythonStartup 'C:\Python312\python.exe' } | Should Throw 'Configuration access denied'
    }
}

Describe 'Python virtual-environment detection under isolation' {
    It 'detects and rejects a real parent pyvenv.cfg without executing site payloads' {
        $fixture = Join-Path $TestDrive 'parent-config'
        & python.exe -I -m venv --without-pip $fixture
        $LASTEXITCODE | Should Be 0
        $interpreter = Join-Path $fixture 'Scripts\python.exe'
        $prefix = & $interpreter -I -c 'import sys; print(sys.prefix)'
        $LASTEXITCODE | Should Be 0
        $prefix | Should Be $fixture
        { Assert-SetupPythonStartup $interpreter } | Should Throw 'standalone Python'
    }
}

Describe 'Installed Chocolatey Python selection' {
    BeforeEach {
        $script:pythonDirectory = 'C:\Python312'
        Mock Test-Path { $true }
        Mock Get-Content {
            '<package xmlns="http://schemas.microsoft.com/packaging/2010/07/nuspec.xsd"><metadata><id>python3</id><version>3.12.9</version></metadata></package>'
        }
        Mock Open-SetupPythonRegistry {
            $key = [pscustomobject]@{ Directory = $script:pythonDirectory }
            $key | Add-Member ScriptMethod GetValue { param($Name) $this.Directory }
            $key | Add-Member ScriptMethod Dispose {}
            $key
        }
        Mock Assert-TrustedSetupPath {}
    }

    It 'returns the registered interpreter, not pip or a PATH match' {
        Get-SetupPython 'C:\ProgramData\chocolatey' | Should Be 'C:\Python312\python.exe'
        Assert-MockCalled Assert-TrustedSetupPath -Times 1 -Exactly -Scope It -ParameterFilter {
            $Path -eq 'C:\Python312' -and $Recurse
        }
        Assert-MockCalled Assert-TrustedSetupPath -Times 1 -Exactly -Scope It -ParameterFilter {
            $Path -eq 'C:\ProgramData\chocolatey\lib\python3\python3.nuspec'
        }
    }

    It 'accepts a unique trusted installation outside the former wildcard' {
        $script:pythonDirectory = 'D:\Tools\Python'
        Get-SetupPython 'C:\ProgramData\chocolatey' | Should Be 'D:\Tools\Python\python.exe'
    }

    It 'looks up only the installed package version even if other Pythons coexist' {
        Mock Open-SetupPythonRegistry {
            if ($Version -ne '3.12') { throw 'Unexpected Python version lookup' }
            $key = [pscustomobject]@{}
            $key | Add-Member ScriptMethod GetValue { param($Name) 'C:\Python312' }
            $key | Add-Member ScriptMethod Dispose {}
            $key
        }
        Get-SetupPython 'C:\ProgramData\chocolatey' | Should Be 'C:\Python312\python.exe'
        Assert-MockCalled Open-SetupPythonRegistry -Times 1 -Exactly -Scope It -ParameterFilter { $Version -eq '3.12' }
    }

    It 'rejects missing machine-wide installations' {
        Mock Open-SetupPythonRegistry { $null }
        { Get-SetupPython 'C:\ProgramData\chocolatey' } | Should Throw 'no machine-wide installation'
        Assert-MockCalled Assert-TrustedSetupPath -Times 0 -Exactly -Scope It -ParameterFilter { $Recurse }
    }

    It 'rejects an unexpected package instead of guessing an interpreter' {
        Mock Get-Content { '<package><metadata><id>other</id><version>3.12.9</version></metadata></package>' }
        { Get-SetupPython 'C:\ProgramData\chocolatey' } | Should Throw 'Cannot determine the installed Python version'
        Assert-MockCalled Open-SetupPythonRegistry -Times 0 -Exactly -Scope It
    }

    It 'rejects an incomplete registration' {
        $script:pythonDirectory = ''
        { Get-SetupPython 'C:\ProgramData\chocolatey' } | Should Throw 'no registered installation directory'
    }

    It 'propagates missing package metadata errors' {
        Mock Get-Content { throw 'Package manifest missing' }
        { Get-SetupPython 'C:\ProgramData\chocolatey' } | Should Throw 'Package manifest missing'
    }

    It 'propagates registry access errors' {
        Mock Open-SetupPythonRegistry { throw 'Registry access denied' }
        { Get-SetupPython 'C:\ProgramData\chocolatey' } | Should Throw 'Registry access denied'
    }

    It 'rejects a missing interpreter instead of falling back to PATH' {
        Mock Test-Path { $PathType -ne 'Leaf' }
        { Get-SetupPython 'C:\ProgramData\chocolatey' } | Should Throw 'registered Python interpreter is missing'
    }

    It 'requires the entire registered installation to be trusted' {
        Mock Assert-TrustedSetupPath { if ($Recurse) { throw 'Untrusted installation' } }
        { Get-SetupPython 'C:\ProgramData\chocolatey' } | Should Throw 'Untrusted installation'
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
        { Assert-TrustedSetupItem $item -Ancestor } | Should Not Throw
        { Assert-TrustedSetupItem $item } | Should Throw 'must not grant write or replacement rights'
    }

    It 'accepts ProgramData-style ancestor write rights without delete or permission-control rights' {
        $item.FullName = 'C:\ProgramData'
        $acl.SetSecurityDescriptorSddlForm('O:SYD:(A;;FA;;;SY)(A;;FA;;;BA)(A;;FW;;;BU)')
        { Assert-TrustedSetupItem $item -Ancestor } | Should Not Throw
        { Assert-TrustedSetupItem $item } | Should Throw 'must not grant write or replacement rights'
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
    BeforeEach {
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

    It 'rejects a protected executable with a sibling-writable immediate directory' {
        Mock Get-Acl {
            $acl = New-SetupDirectoryAcl
            if ($LiteralPath -eq 'C:\ProgramData\chocolatey') {
                $acl.SetSecurityDescriptorSddlForm('O:BAD:(A;;FA;;;BA)(A;;FW;;;BU)')
            }
            $acl
        }
        { Invoke-SetupCommand 'C:\ProgramData\chocolatey\choco.exe' @() } |
            Should Throw 'must not grant write or replacement rights'
    }

    It 'rejects an unsafe real Chocolatey executable independently of its bin shim' {
        Mock Get-Acl {
            $acl = New-SetupDirectoryAcl
            if ($LiteralPath -eq 'C:\ProgramData\chocolatey\choco.exe') {
                $acl.SetSecurityDescriptorSddlForm('O:BAD:(A;;FA;;;BA)(A;;FW;;;BU)')
            }
            $acl
        }
        { Invoke-SetupCommand 'C:\ProgramData\chocolatey\choco.exe' @() } |
            Should Throw 'must not grant write or replacement rights'
        Assert-MockCalled Get-Acl -Times 0 -Exactly -Scope It -ParameterFilter {
            $LiteralPath -eq 'C:\ProgramData\chocolatey\bin\choco.exe'
        }
    }

    It 'allows harmless sibling creation on a more distant ancestor' {
        Mock Get-Acl {
            $acl = New-SetupDirectoryAcl
            if ($LiteralPath -eq 'C:\ProgramData') {
                $acl.SetSecurityDescriptorSddlForm('O:SYD:(A;;FA;;;SY)(A;;FA;;;BA)(A;;FW;;;BU)')
            }
            $acl
        }
        { Assert-TrustedSetupPath 'C:\ProgramData\chocolatey' } | Should Not Throw
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
    BeforeEach {
        Mock Test-Path { $true }
        Mock Assert-TrustedSetupPath {}
        Mock Remove-Item {}
        # These cases must stop before the real Directory.CreateDirectory call.
        Mock New-SetupDirectoryAcl { throw 'Unexpected directory creation' }
    }

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

Describe 'Raw machine PATH registry persistence' {
    BeforeEach {
        $script:environmentKey = [pscustomobject]@{
            Path = '%JAVA_HOME%\bin;%SystemRoot%\System32'
            Kind = [Microsoft.Win32.RegistryValueKind]::ExpandString
            WrittenPath = $null
            WrittenKind = $null
            ReadOptions = $null
            Disposed = $false
            FailRead = $false
            FailWrite = $false
        }
        $environmentKey | Add-Member ScriptMethod GetValue {
            param($Name, $Default, $Options)
            if ($this.FailRead) { throw 'Registry read denied' }
            $this.ReadOptions = $Options
            if ($Options -ne [Microsoft.Win32.RegistryValueOptions]::DoNotExpandEnvironmentNames) {
                throw 'Machine PATH must not be expanded'
            }
            $this.Path
        }
        $environmentKey | Add-Member ScriptMethod GetValueKind { param($Name) $this.Kind }
        $environmentKey | Add-Member ScriptMethod SetValue {
            param($Name, $Value, $Kind)
            if ($this.FailWrite) { throw 'Registry write denied' }
            $this.WrittenPath = $Value
            $this.WrittenKind = $Kind
        }
        $environmentKey | Add-Member ScriptMethod Dispose { $this.Disposed = $true }
        Mock Open-SetupEnvironmentRegistry { $script:environmentKey }
        Mock Send-SetupEnvironmentChange {
            $script:environmentKey.Disposed | Should Be $true
            $script:environmentKey.WrittenPath | Should Not BeNullOrEmpty
        }
    }

    It 'preserves raw references and REG_EXPAND_SZ despite a conflicting process value' {
        $previous = $env:JAVA_HOME
        try {
            $env:JAVA_HOME = 'C:\UntrustedProcessOverride'
            Add-SetupMachinePath @('C:\php', 'C:\Program Files\Git\cmd')
        }
        finally { $env:JAVA_HOME = $previous }
        $environmentKey.WrittenPath | Should Be '%JAVA_HOME%\bin;%SystemRoot%\System32;C:\php;C:\Program Files\Git\cmd'
        $environmentKey.WrittenKind | Should Be ([Microsoft.Win32.RegistryValueKind]::ExpandString)
        Assert-MockCalled Send-SetupEnvironmentChange -Times 1 -Exactly -Scope It
    }

    It 'preserves REG_SZ without interpreting its percent references' {
        $environmentKey.Kind = [Microsoft.Win32.RegistryValueKind]::String
        Add-SetupMachinePath @('C:\php')
        $environmentKey.WrittenPath | Should Be '%JAVA_HOME%\bin;%SystemRoot%\System32;C:\php'
        $environmentKey.WrittenKind | Should Be ([Microsoft.Win32.RegistryValueKind]::String)
    }

    It 'initializes a missing PATH as REG_EXPAND_SZ' {
        $environmentKey.Path = $null
        Add-SetupMachinePath @('C:\php')
        $environmentKey.WrittenPath | Should Be 'C:\php'
        $environmentKey.WrittenKind | Should Be ([Microsoft.Win32.RegistryValueKind]::ExpandString)
    }

    It 'preserves an existing empty REG_SZ value kind' {
        $environmentKey.Path = ''
        $environmentKey.Kind = [Microsoft.Win32.RegistryValueKind]::String
        Add-SetupMachinePath @('C:\php')
        $environmentKey.WrittenPath | Should Be 'C:\php'
        $environmentKey.WrittenKind | Should Be ([Microsoft.Win32.RegistryValueKind]::String)
    }

    It 'rejects unexpected registry value kinds without writing or notifying' {
        $environmentKey.Kind = [Microsoft.Win32.RegistryValueKind]::MultiString
        { Add-SetupMachinePath @('C:\php') } | Should Throw 'must be a string registry value'
        $environmentKey.WrittenPath | Should BeNullOrEmpty
        $environmentKey.Disposed | Should Be $true
        Assert-MockCalled Send-SetupEnvironmentChange -Times 0 -Exactly -Scope It
    }

    It 'reports a missing environment key' {
        Mock Open-SetupEnvironmentRegistry { $null }
        { Add-SetupMachinePath @('C:\php') } | Should Throw 'Cannot open'
        Assert-MockCalled Send-SetupEnvironmentChange -Times 0 -Exactly -Scope It
    }

    foreach ($operation in @('Read', 'Write')) {
        It "propagates registry $operation errors and disposes the key" {
            $environmentKey."Fail$operation" = $true
            { Add-SetupMachinePath @('C:\php') } | Should Throw "Registry $($operation.ToLowerInvariant()) denied"
            $environmentKey.Disposed | Should Be $true
            Assert-MockCalled Send-SetupEnvironmentChange -Times 0 -Exactly -Scope It
        }
    }

    It 'does not hide notification failure after a successful write' {
        Mock Send-SetupEnvironmentChange { throw 'Environment notification failed' }
        { Add-SetupMachinePath @('C:\php') } | Should Throw 'Environment notification failed'
        $environmentKey.Disposed | Should Be $true
        $environmentKey.WrittenPath | Should Not BeNullOrEmpty
    }

    It 'compiles the Unicode notification signature without broadcasting on the test host' {
        $definition = $setupAst.Find({
            param($node)
            $node -is [Management.Automation.Language.CommandAst] -and $node.GetCommandName() -eq 'Add-Type'
        }, $true)
        $code = $definition.CommandElements[-1].Value
        Add-Type -TypeDefinition $code
        $method = [SetupEnvironmentNotification].GetMethod('SendMessageTimeout')
        $method.ReturnType | Should Be ([IntPtr])
        $method.GetParameters()[-1].ParameterType | Should Be ([UIntPtr].MakeByRefType())
    }
}

Describe 'Hosted test runner failure handling' {
    $workflowFile = Join-Path $PSScriptRoot '..\..\.github\workflows\windows-performance-setup.yml'
    $workflow = Get-Content -LiteralPath $workflowFile -Raw
    $blocks = [regex]::Matches($workflow, '(?m)^        run: \|\r?\n((?:          .*(?:\r?\n|$))+)')
    if ($blocks.Count -ne 2) { throw 'Expected both Windows test runner blocks' }
    $nativeShell = Join-Path $PSHOME 'powershell.exe'
    $direct = [regex]::Replace($blocks[0].Groups[1].Value, '(?m)^          ', '')
    $outer = [regex]::Replace($blocks[1].Groups[1].Value, '(?m)^          ', '')

    foreach ($runner in @($direct, $outer)) {
        foreach ($case in @('load-error', 'invoke-error', 'null', 'missing-counts', 'zero', 'failed', 'success')) {
            It "fails closed for $case (32-bit wrapper: $($runner -eq $outer))" {
                $stub = @"
function Import-Module {
    if ('$case' -eq 'load-error') { Write-Error 'Simulated Pester load failure' }
}
function Invoke-Pester {
    if ('$case' -eq 'invoke-error') { Write-Error 'Simulated test discovery failure'; return }
    if ('$case' -eq 'null') { return }
    if ('$case' -eq 'missing-counts') { return [pscustomobject]@{} }
    if ('$case' -eq 'zero') { return [pscustomobject]@{ PassedCount = 0; FailedCount = 0 } }
    if ('$case' -eq 'failed') { return [pscustomobject]@{ PassedCount = 1; FailedCount = 1 } }
    return [pscustomobject]@{ PassedCount = 1; FailedCount = 0 }
}
"@
                if ($runner -eq $outer) {
                    $command = $runner.Replace("`$command = @'`n", "`$command = @'`n$stub`n")
                    $command = $command.Replace("`$command = @'`r`n", "`$command = @'`r`n$stub`r`n")
                }
                else { $command = $stub + "`n" + $runner }
                $encoded = [Convert]::ToBase64String([Text.Encoding]::Unicode.GetBytes($command))
                $previousPreference = $ErrorActionPreference
                try {
                    # Inspect expected child failures without turning native stderr into a parent exception.
                    $ErrorActionPreference = 'Continue'
                    $output = & $nativeShell -NoProfile -NonInteractive -EncodedCommand $encoded 2>&1
                    $exitCode = $LASTEXITCODE
                }
                finally { $ErrorActionPreference = $previousPreference }
                if ($case -eq 'success') { $exitCode | Should Be 0 }
                else { $exitCode | Should Not Be 0 }
            }
        }
    }
}

Describe 'Provisioning wiring (static only)' {
    $source = $setupAst.Extent.Text

    It 'uses isolated module lookup with the selected Python interpreter' {
        $source | Should Match ([regex]::Escape("Invoke-SetupPip `$pythonExe @('install', 'pyodbc')"))
        $source | Should Match ([regex]::Escape("Invoke-SetupCommand `$Executable (@('-I', '-m', 'pip') + `$Arguments)"))
        $source | Should Not Match ([regex]::Escape('C:\Python*\Scripts\pip3.exe'))
    }

    It 'uses fully qualified PHP for both Composer steps' {
        $source | Should Match ([regex]::Escape("Invoke-SetupCommand `$phpExe @('composer-setup.php')"))
        $source | Should Match ([regex]::Escape("Invoke-SetupCommand `$phpExe @('composer.phar', 'install')"))
    }

    It 'persists machine PATH plus only the PHP and Git directories' {
        $source | Should Match ([regex]::Escape('Add-SetupMachinePath @($phpDir, $gitDir)'))
        $source | Should Not Match 'GetEnvironmentVariable\("Path".*Machine'
        $source | Should Not Match 'SetEnvironmentVariable\("Path".*Machine'
        $source | Should Match ([regex]::Escape("`$gitDir = 'C:\Program Files\Git\cmd'"))
        $source | Should Match ([regex]::Escape('$env:Path = Add-SetupPath $env:Path @($phpDir, $gitDir)'))
    }

    It 'creates PHP with its protected DACL and validates it before use' {
        $source | Should Match ([regex]::Escape('[IO.Directory]::CreateDirectory($Path, $acl)'))
        $source | Should Match 'New-SetupPhpDirectory \$phpDir\s+Expand-Archive.*\r?\nAssert-TrustedSetupPath \$phpDir -Recurse'
        $source.LastIndexOf('Assert-TrustedSetupPath $phpDir -Recurse') |
            Should BeLessThan $source.IndexOf('Add-SetupMachinePath @($phpDir, $gitDir)')
    }

    It 'keeps installer failures on the checked native-command path' {
        $source | Should Match ([regex]::Escape("`$chocoExe = Join-Path `$chocoDir 'choco.exe'"))
        $source | Should Not Match ([regex]::Escape("Join-Path `$chocoDir 'bin\choco.exe'"))
        foreach ($package in @('git', 'python3', '7zip.install')) {
            $source | Should Match ([regex]::Escape("Invoke-SetupCommand `$chocoExe @('install', '-y', '$package') @(0, 1641, 3010)"))
        }
    }

    It 'does not scan unrelated Chocolatey packages or Git installation subtrees' {
        $source | Should Not Match ([regex]::Escape('Assert-TrustedSetupPath $chocoDir -Recurse'))
        $source | Should Match ([regex]::Escape('Assert-TrustedSetupPath $gitDir -Recurse'))
    }

    It 'leaves the ODBC MSI command and Windows library copies unchanged' {
        $source | Should Match ([regex]::Escape('msiexec /quiet /passive /qn /i $tempFolder\msodbcsql.msi IACCEPTMSODBCSQLLICENSETERMS=YES | Out-Null'))
        $source | Should Match ([regex]::Escape('Copy-Item $phpDir\ssleay32.dll C:\Windows -force'))
        $source | Should Match ([regex]::Escape('Copy-Item $phpDir\libeay32.dll C:\Windows -force'))
    }
}
