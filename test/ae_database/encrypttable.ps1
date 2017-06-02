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

# Encrypt the selected columns (or re-encrypt, if they are already encrypted using keys/encrypt types, different than the specified keys/types.
$ces = @()
$ces += New-SqlColumnEncryptionSettings -ColumnName "test_AE_exnum.encDetBigint" -EncryptionType "Deterministic" -EncryptionKey "CEK1"
$ces += New-SqlColumnEncryptionSettings -ColumnName "test_AE_exnum.encRandBigint" -EncryptionType "Randomized" -EncryptionKey "CEK1"
Set-SqlColumnEncryption -InputObject $database -ColumnEncryptionSettings $ces
