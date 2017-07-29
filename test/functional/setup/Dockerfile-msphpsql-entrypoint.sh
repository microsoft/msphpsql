set -e

db_migrate="/opt/mssql-tools/bin/sqlcmd -S sql -U sa -P Password123  -Q "select @@Version"

until $db_migrate; do
>&2 echo "SQL Server is starting up. Running initial db configuration"
sleep 1
done

>&2 echo "SQL Server is up - starting app"
