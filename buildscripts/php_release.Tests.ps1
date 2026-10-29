$ErrorActionPreference = 'Stop'
$pipeline = Get-Content -LiteralPath (Join-Path $PSScriptRoot '..\azure-pipelines.yml') -Raw
$selection = [regex]::Match($pipeline, '(?m)^\s*\$vsVersion = if .+$').Value.Trim()
$download = [regex]::Match($pipeline, '(?m)^\s*\$phpUrl = "https://windows\.php\.net/downloads/releases/php-.+$').Value.Trim()
if (-not $selection -or -not $download) {
    throw 'Could not locate Windows PHP release selection in azure-pipelines.yml.'
}

# Evaluate only the two assignments under test, never the provisioning steps.
Describe 'Windows PHP release selection' {
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
}
