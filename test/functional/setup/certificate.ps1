Param(
    [Parameter(Mandatory=$True,Position=1)]
    [string]$serverName,
    [Parameter(Mandatory=$True,Position=2)]
    [string]$databaseName,
    [Parameter(Mandatory=$True,Position=3)]
    [string]$userName,
    [Parameter(Mandatory=$True,Position=4)]
    [string]$password)

# Create a column master key in Windows Certificate Store.
$cert1 = New-SelfSignedCertificate -Subject "PHPAlwaysEncryptedCert" -CertStoreLocation Cert:CurrentUser\My -KeyExportPolicy Exportable -Type DocumentEncryptionCert -KeyUsage DataEncipherment -KeySpec KeyExchange

# Import the SqlServer module.
Import-Module "SqlServer"

#For SQL Server Authentication
Add-Type -AssemblyName "Microsoft.SqlServer.Smo"
$MySQL = new-object('Microsoft.SqlServer.Management.Smo.Server') $serverName 
$MySQL.ConnectionContext.LoginSecure = $false
$MySQL.ConnectionContext.set_Login($userName)
$MySQL.ConnectionContext.set_Password($password)
$database = $MySQL.Databases[$databaseName]

# Create a SqlColumnMasterKeySettings object for your column master key. 
$cmkSettings = New-SqlCertificateStoreColumnMasterKeySettings -CertificateStoreLocation "CurrentUser" -Thumbprint $cert1.Thumbprint

# Create column master key metadata in the database.
$cmkName = "CMK2"
New-SqlColumnMasterKey -Name $cmkName -InputObject $database -ColumnMasterKeySettings $cmkSettings

# Generate a column encryption key, encrypt it with the column master key and create column encryption key metadata in the database. 
$cekName = "CEK2"
New-SqlColumnEncryptionKey -Name $cekName  -InputObject $database -ColumnMasterKey $cmkName

# Disconnect
$MySQL.ConnectionContext.Disconnect()