$ErrorActionPreference = 'Stop'
$pipeline = Get-Content -LiteralPath (Join-Path $PSScriptRoot '..\azure-pipelines.yml') -Raw
$selection = [regex]::Match($pipeline, '(?m)^\s*\$vsVersion = if .+$').Value.Trim()
$download = [regex]::Match($pipeline, '(?m)^\s*\$phpUrl = "https://windows\.php\.net/downloads/releases/php-.+$').Value.Trim()
$buildVersion = [regex]::Match($pipeline, '(?m)^\s*\$env:VERSION = .+$').Value.Trim()
if (-not $selection -or -not $download -or -not $buildVersion) {
    throw 'Could not locate Windows PHP release selection in azure-pipelines.yml.'
}
$buildVersion = $buildVersion.Replace('$(phpVersion)', '8.5').Replace('$env:VERSION', '$sourceVersion')

# Evaluate only the assignments under test, never the provisioning steps.
Describe 'Windows PHP release selection' {
    It 'uses a hosted image with the VS17 compiler required by PHP' {
        $windowsJob = [regex]::Match($pipeline, '(?s)- job: Windows\r?\n.*?\r?\n  steps:').Value
        $windowsJob | Should Match "vmImage: 'windows-2022'"
    }

    It 'selects VS17 for an ordinary PHP 8.5 release' {
        $version = '8.5.0'
        . ([scriptblock]::Create($selection))
        $vsVersion | Should Be 'vs17'
    }

    It 'accepts the repackaged release and preserves its complete archive name' {
        $version = '8.5.12-1'
        . ([scriptblock]::Create($selection))
        . ([scriptblock]::Create($download))
        $vsVersion | Should Be 'vs17'
        $version | Should Be '8.5.12-1'
        $phpUrl | Should Be 'https://windows.php.net/downloads/releases/php-8.5.12-1-nts-Win32-vs17-x64.zip'
    }

    It 'preserves the existing earlier-version compiler selection' {
        $version = '8.3.30-2'
        . ([scriptblock]::Create($selection))
        $vsVersion | Should Be 'vs16'
    }

    It 'does not choose a compiler silently for malformed version data' {
        $version = 'invalid-1'
        { . ([scriptblock]::Create($selection)) } | Should Throw
    }

    It 'uses the upstream Git tag version for a repackaged PHP build' {
        $jsondata = '{"8.5":{"version":"8.5.12-1"}}' | ConvertFrom-Json
        . ([scriptblock]::Create($buildVersion))
        $sourceVersion | Should Be '8.5.12'
        ('php-' + $sourceVersion.Replace('-', '')) | Should Be 'php-8.5.12'
    }

    It 'preserves an ordinary upstream source version' {
        $jsondata = '{"8.5":{"version":"8.5.0"}}' | ConvertFrom-Json
        . ([scriptblock]::Create($buildVersion))
        $sourceVersion | Should Be '8.5.0'
    }
}
