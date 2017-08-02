set -e

testConnection="/opt/mssql-tools/bin/sqlcmd -S sql -U sa -P Password123"

until $testConnection; do
>&2 echo "SQL Server is starting up.."
sleep 1
done

>&2 echo "SQL Server is up!"
