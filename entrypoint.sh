set -e

testConnection="/opt/mssql-tools/bin/sqlcmd -S sql -U sa -P Password123"

for run in {1..10}; do

>&2 echo "SQL Server is starting up.."
if $testConnection; then
    >&2 echo "SQL Server is up!"
    break;
else
    sleep 6
fi
done


