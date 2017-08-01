set -e

$test = "sqlcmd  -S sql -U sa -P Password123"

until $test; do
>&2 echo "SQL Server is starting up. Running initial db configuration"
sleep 1
done

echo "SQL Server is up - starting app"