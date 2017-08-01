set -e

$test ='/opt/mssql-tools/bin/sqlcmd -S sql -U sa -P Password123'

until $test; do
>&2 echo "SQL Server is starting up"
sleep 1
done

echo "SQL Server is up - starting app"