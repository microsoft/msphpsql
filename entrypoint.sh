set -e

while ! ping -c3 $1 &>/dev/null; do echo "Ping Fail - `date`"; done ; echo "Host Found - `date`";

testConnection="/opt/mssql-tools/bin/sqlcmd -S $1 -U $2 -P $3"

until $testConnection; do
>&2 echo "SQL Server is starting up.."
sleep 1
done

>&2 echo "SQL Server is up!"
