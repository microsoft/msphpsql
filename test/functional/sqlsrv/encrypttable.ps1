Param(
    [Parameter(Mandatory=$True,Position=1)]
    [string]$serverName,
    [Parameter(Mandatory=$True,Position=2)]
    [string]$databaseName,
    [Parameter(Mandatory=$True,Position=3)]
    [string]$userName,
    [Parameter(Mandatory=$True,Position=4)]
    [string]$password,
    [Parameter(Mandatory=$True,Position=5)]
    [string]$tableName,
    [Parameter(Mandatory=$True,Position=6)]
    [string]$columnNames)

# Import the SqlServer module.
Import-Module "SqlServer"

Write-Host $columnNames

#For SQL Server Authentication
Add-Type -AssemblyName "Microsoft.SqlServer.Smo"
$MySQL = new-object('Microsoft.SqlServer.Management.Smo.Server') $serverName 
$MySQL.ConnectionContext.LoginSecure = $false
$MySQL.ConnectionContext.set_Login($userName)
$MySQL.ConnectionContext.set_Password($password)
$database = $MySQL.Databases[$databaseName]

#split the column names into an array
$column_arr = $columnNames.Split(",")

# Encrypt the selected columns (or re-encrypt, if they are already encrypted using keys/encrypt types, different than the specified keys/types.
$ces = @()
foreach($col_name in $column_arr){
    $col_full_name = "$tableName.$col_name"
    if($col_name -like '*det*'){
        $ces += New-SqlColumnEncryptionSettings -ColumnName $col_full_name -EncryptionType "Deterministic" -EncryptionKey "CEK1"
    }
    elseif($col_name -like '*rand*'){
        $ces += New-SqlColumnEncryptionSettings -ColumnName $col_full_name -EncryptionType "Randomized" -EncryptionKey "CEK1"
    }
}
Set-SqlColumnEncryption -InputObject $database -ColumnEncryptionSettings $ces

# Disconnect
$MySQL.ConnectionContext.Disconnect()