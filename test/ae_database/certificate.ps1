# Create a column master key in Windows Certificate Store.
$cert1 = New-SelfSignedCertificate -Subject "AlwaysEncryptedCert" -CertStoreLocation Cert:CurrentUser\My -KeyExportPolicy Exportable -Type DocumentEncryptionCert -KeyUsage DataEncipherment -KeySpec KeyExchange

# Import the SqlServer module.
Import-Module "SqlServer"

# Connect to your database.
$serverName = "yourServer"
$databaseName = "AEDemo"
$userName = "yourUsername";
$password = "yourPassword";

#For Windows Authentication
#$connStr = "Server = " + $serverName + "; Database = " + $databaseName + "; User Id = " + $userName + "; Password = " + $password
#$connection = New-Object Microsoft.SqlServer.Management.Common.ServerConnection
#$connection.ConnectionString = $connStr
#$connection.ConnectionString = "Server = $serverName; Database = $databaseName; User ID = $userName; Password = $password;"
#$connection.Connect()
#$server = New-Object Microsoft.SqlServer.Management.Smo.Server($connection)
#$database = $server.Databases[$databaseName]

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
$cmkName = "CMK1"
New-SqlColumnMasterKey -Name $cmkName -InputObject $database -ColumnMasterKeySettings $cmkSettings

# Generate a column encryption key, encrypt it with the column master key and create column encryption key metadata in the database. 
$cekName = "CEK1"
New-SqlColumnEncryptionKey -Name $cekName  -InputObject $database -ColumnMasterKey $cmkName