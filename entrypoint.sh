set -e
isConnected="/opt/mssql-tools/bin/sqlcmd -S $1 -U $2 -P $3"

until $isConnected; do
>&2 echo "SQL Server is starting up..."
sleep 1
done

>&2 echo "SQL Server is up!"
