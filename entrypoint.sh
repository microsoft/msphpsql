set -e
isConnected="/opt/mssql-tools/bin/sqlcmd -S db -U sa -P Password123"

until $isConnected; do
>&2 echo "SQL Server is starting up. Running initial db configuration"
sleep 1
done

>&2 echo "SQL Server is up - starting app"
