# Performance Test Results for the Microsoft Drivers for PHP for SQL Server

This page lists benchmarking results for both the SQLSRV and PDO_SQLSRV drivers for various operations and various environments. Test are performed on the following client environment:
* PHP 7.1.7
* Driver version 5.0.0
* Platform: x64
* Non-thread safe
* ODBC Driver version 13.1

The client OS includes:
* Windows Server 2016
* MacOS Sierra
* Red Hat 7.2
* Ubuntu 16.04
* SUSE 12

The server testing environment is either 
* Windows Server 2016 with SQL Server 2016, or 
* Ubuntu 16.04 with SQL Server Linux 2016

The following table lists benchmarking results for each scenario tested. Listed are the times taken for all iterations, and the maximum memory usage for all iterations in each scenario.

| | | | SQLSRV | | | PDO_SQLSRV | | |
| :----- | :----- | :----- | :----- | -----: | -----: | :-----| -----: | -----: |
|Scenario|Server|Client|Iterations|Memory(MB)|Time(s)|Iterations|Memory(MB)|Time(s)|
|CRUD       |SQL Server Windows|Windows Server 2016|1000x100 rows|0.88667|1074|1000x100 rows|0.88562|1195|
|           |                  |OS X Sierra        |1000x100 rows|0.88781|941 |1000x100 rows|0.88678|1019|
|           |                  |Red Hat 7.2        |1000x100 rows|0.88223|1053|1000x100 rows|0.88120|1164|
|           |                  |Ubuntu 16.04       |1000x100 rows|0.88056|1059|1000x100 rows|0.87954|1172|
|           |                  |Suse 12            |1000x100 rows|0.88945|978 |1000x100 rows|0.88849|1084|
|           |SQL Server Linux  |Windows Server 2016|             |       |    |             |       |    |
|           |                  |OS X Sierra        |1000x100 rows|0.88781|1112|1000x100 rows|0.88678|1210|
|           |                  |Red Hat 7.2        |1000x100 rows|0.88223|1189|1000x100 rows|0.88120|1315|
|           |                  |Ubuntu 16.04       |1000x100 rows|0.88056|1199|             |       |    |
|           |                  |Suse 12            |             |       |    |             |       |    |
|CRUD Insert|SQL Server Windows|Windows Server 2016|1000x100 rows|0.88380|311|1000x100 rows|0.88264|319|
|           |                  |OS X Sierra        |1000x100 rows|0.88770|280|1000x100 rows|0.88660|282|
|           |                  |Red Hat 7.2        |1000x100 rows|0.88342|304|1000x100 rows|0.88229|309|
|           |                  |Ubuntu 16.04       |1000x100 rows|0.88171|309|1000x100 rows|0.88065|357|
|           |                  |Suse 12            |1000x100 rows|0.88655|285|1000x100 rows|0.88548|290|
|           |SQL Server Linux  |Windows Server 2016|1000x100 rows|0.88681|368|1000x100 rows|0.88568|387|
|           |                  |OS X Sierra        |1000x100 rows|0.88770|348|1000x100 rows|0.88660|359|
|           |                  |Red Hat 7.2        |1000x100 rows|0.88342|349|1000x100 rows|0.88230|358|
|           |                  |Ubuntu 16.04       |1000x100 rows|0.88179|357|1000x100 rows|0.88067|363|
|           |                  |Suse 12            |1000x100 rows|0.88655|342|1000x100 rows|0.88548|344|
|CRUD Fetch |SQL Server Windows|Windows Server 2016|1000x100 rows|0.88431|255|1000x100 rows|0.88318|250|
|           |                  |OS X Sierra        |1000x100 rows|0.88828|188|1000x100 rows|0.88718|187|
|           |                  |Red Hat 7.2        |1000x100 rows|0.88396|248|1000x100 rows|0.88284|245|
|           |                  |Ubuntu 16.04       |1000x100 rows|0.88226|250|1000x100 rows|0.87514|247|
|           |                  |Suse 12            |1000x100 rows|0.88709|228|1000x100 rows|0.88602|227|
|           |SQL Server Linux  |Windows Server 2016|1000x100 rows|0.88735|287|1000x100 rows|0.88622|282|
|           |                  |OS X Sierra        |1000x100 rows|0.88828|229|1000x100 rows|0.88718|228|
|           |                  |Red Hat 7.2        |1000x100 rows|0.88400|272|1000x100 rows|0.88287|270|
|           |                  |Ubuntu 16.04       |1000x100 rows|0.88236|276|1000x100 rows|0.88124|274|
|           |                  |Suse 12            |1000x100 rows|0.88709|257|1000x100 rows|0.88602|254|
|CRUD Update|SQL Server Windows|Windows Server 2016|1000x100 rows|0.88594|314|1000x100 rows|0.88478|323|
|           |                  |OS X Sierra        |1000x100 rows|0.88988|284|1000x100 rows|0.88879|285|
|           |                  |Red Hat 7.2        |1000x100 rows|0.88560|307|1000x100 rows|0.88448|313|
|           |                  |Ubuntu 16.04       |1000x100 rows|0.87787|310|1000x100 rows|0.88235|327|
|           |                  |Suse 12            |1000x100 rows|0.88869|288|1000x100 rows|0.88762|294|
|           |SQL Server Linux  |Windows Server 2016|1000x100 rows|0.88899|369|1000x100 rows|0.88786|376|
|           |                  |OS X Sierra        |1000x100 rows|0.88988|349|1000x100 rows|0.88879|362|
|           |                  |Red Hat 7.2        |1000x100 rows|0.88560|357|1000x100 rows|0.88448|362|
|           |                  |Ubuntu 16.04       |1000x100 rows|0.88397|361|1000x100 rows|0.88285|367|
|           |                  |Suse 12            |1000x100 rows|0.88873|343|1000x100 rows|0.88766|340|
|CRUD Delete|SQL Server Windows|Windows Server 2016|1000x100 rows|0.88452|197|1000x100 rows|0.88341|303|
|           |                  |OS X Sierra        |1000x100 rows|0.88843|188|1000x100 rows|0.88737|274|
|           |                  |Red Hat 7.2        |1000x100 rows|0.88411|192|1000x100 rows|0.88303|295|
|           |                  |Ubuntu 16.04       |1000x100 rows|0.87638|193|1000x100 rows|0.88094|298|
|           |                  |Suse 12            |1000x100 rows|0.88728|181|1000x100 rows|0.88625|277|
|           |SQL Server Linux  |Windows Server 2016|1000x100 rows|0.88754|233|1000x100 rows|0.88645|358|
|           |                  |OS X Sierra        |1000x100 rows|0.88843|243|1000x100 rows|0.88737|340|
|           |                  |Red Hat 7.2        |1000x100 rows|0.88415|229|1000x100 rows|0.88307|342|
|           |                  |Ubuntu 16.04       |1000x100 rows|0.88251|229|1000x100 rows|0.88144|347|
|           |                  |Suse 12            |1000x100 rows|0.88728|220|1000x100 rows|0.88625|328|
|Large Read - 10,000,000 rows  |SQL Server Windows|Windows Server 2016|1|0.88117|606|1|0.88003|382|
|                              |                  |OS X Sierra        |1|0.88572|420|1|0.88463|344|
|                              |                  |Red Hat 7.2        |1|0.88145|495|1|0.88035|694|
|                              |                  |Ubuntu 16.04       |1|0.87981|413|1|0.87259|585|
|                              |                  |Suse 12            |1|0.88397|453|1|0.88291|326|
|                              |SQL Server Linux  |Windows Server 2016|1|0.88423|685| |       |   |
|                              |                  |OS X Sierra        |1|0.88571|308| |       |   |
|                              |                  |Red Hat 7.2        |1|0.88140|512| |       |   |
|                              |                  |Ubuntu 16.04       |1|0.87977|749| |       |   |
|                              |                  |Suse 12            |1|0.88397|828| |       |   |
|SELECT @@Version|SQL Server Windows|Windows Server 2016|10,000|0.88074|16|10,000|0.87955|28|
|                |                  |OS X Sierra        |10,000|0.88522|19|10,000|0.88412|33|
|                |                  |Red Hat 7.2        |10,000|0.88118|11|10,000|0.88006|22|
|                |                  |Ubuntu 16.04       |      |       |  |      |       |  |
|                |                  |Suse 12            |10,000|0.88351|13|10,000|0.88243|24|
|                |SQL Server Linux  |Windows Server 2016|10,000|0.88377|16|10,000|0.88264|30|
|                |                  |OS X Sierra        |10,000|0.88522|19|10,000|0.88412|33|
|                |                  |Red Hat 7.2        |10,000|0.88094|17|10,000|0.87981|28|
|                |                  |Ubuntu 16.04       |10,000|0.87930|16|10,000|0.87818|26|
|                |                  |Suse 12            |10,000|0.88351|15|10,000|0.88243|26|
|CREATE DATABASE/TABLE/STORED PROCS|SQL Server Windows|Windows Server 2016|1000|0.88237| 515|1000|0.88123| 529|
|                                  |                  |OS X Sierra        |1000|0.88638| 420|1000|0.88530| 457|
|                                  |                  |Red Hat 7.2        |1000|0.88213| 434|1000|0.88102| 447|
|                                  |                  |Ubuntu 16.04       |1000|0.87437| 395|    |       |    |
|                                  |                  |Suse 12            |1000|0.88512| 494|1000|0.88406| 507|
|                                  |SQL Server Linux  |Windows Server 2016|1000|0.88544|2285|1000|0.88433|2324|
|                                  |                  |OS X Sierra        |1000|0.88638|2338|1000|0.88530|2352|
|                                  |                  |Red Hat 7.2        |1000|0.88216|2292|1000|0.88106|2312|
|                                  |                  |Ubuntu 16.04       |1000|0.80530|2340|1000|0.87943|1228|
|                                  |                  |Suse 12            |1000|0.88512|2341|1000|0.88406|2337|
|Open and close 1000 connections|SQL Server Windows|Windows Server 2016|1000|0.87956|39|1000|0.87843|38|
|                               |                  |OS X Sierra        |1000|0.88363|62|1000|0.88255|62|
|                               |                  |Red Hat 7.2        |1000|0.87935|30|1000|0.87825|30|
|                               |                  |Ubuntu 16.04       |    |       |  |1000|0.87608|29|
|                               |                  |Suse 12            |1000|0.88232|24|1000|0.88126|25|
|                               |SQL Server Linux  |Windows Server 2016|1000|0.88267|45|1000|0.88156|45|
|                               |                  |OS X Sierra        |1000|0.88363|65|1000|0.88255|65|
|                               |                  |Red Hat 7.2        |1000|0.87932|35|1000|0.87822|35|
|                               |                  |Ubuntu 16.04       |1000|0.87771|35|1000|0.87659|34|
|                               |                  |Suse 12            |1000|0.88232|29|1000|0.88126|30|
|Open and close 1000 connections with connection pooling|SQL Server Windows|Windows Server 2016|||||||
|                                                       |                  |OS X Sierra        |||||||
|                                                       |                  |Red Hat 7.2        |||||||
|                                                       |                  |Ubuntu 16.04       |||||||
|                                                       |                  |Suse 12            |||||||
|                                                       |SQL Server Linux  |Windows Server 2016|||||||
|                                                       |                  |OS X Sierra        |||||||
|                                                       |                  |Red Hat 7.2        |||||||
|                                                       |                  |Ubuntu 16.04       |||||||
|                                                       |                  |Suse 12            |||||||

The following table lists details of each scenario tested, including the SQL statements and data types used.

|Scenario|Description|Iterations|Operations/Iteration|t-SQL|Column Datatypes|
|---|---|---|---|---|---|
|CRUD|Contains a loop for inserting a row into, fetching from, updating a row in, and deleting a row from a table. Measurement starts immediately before preparing the first INSERT INTO statement and ends immediately after the 100th DELETE statement executes.|1000|100|<ul><li>INSERT INTO &lt;tableName&gt; VALUES (&lt;params&gt;)</li><li>SELECT \* FROM &lt;tableName&gt;</li><li>UPDATE &lt;tableName&gt; SET &lt;params&gt;</li><li>DELETE TOP(1) FROM &lt;tableName&gt;</li></ul>|<ul><li>VARCHAR(64)</li><li>NVARCHAR(64)</li><li>INT</li><li>DATETIME2</li><li>CHAR(64)</li><li>NCHAR(64)</li><li>NUMERIC</li><li>BINARY(64)</li><li>VARBINARY</li><li>DATETIMEOFFSET</li></ul>|
|CRUD Insert|Contains a loop for inserting into a table. Each iteration prepares a statement, binds params, and executes. Measurement starts immediately before preparing the first INSERT INTO statement and ends immediately after the 100th statement executes.|1000|100|INSERT INTO &lt;tableName&gt; VALUES (&lt;params&gt;)||
|CRUD Fetch|Contains a loop for fetching from a table with data inserted using the t-SQL in CRUD Insert. Each iteration prepares and executes a statement, and fetches a row from the result set. Measurement starts immediately before preparing the first SELECT statmeent and ends immediately after the 100th fetch.|1000|100|SELECT \* FROM &lt;tableName&gt;|
|CRUD Update|Contains a loop for updating a table with one row populated using the t-SQL in CRUD Insert. Each iteration prepares a statement, binds params, and executes. Measurement starts immediately before preparing the 1st UPDATE statement and ends immediately after the 100th statement executes.|1000|100|UPDATE &lt;tableName&gt; SET &lt;params&gt;|
|CRUD Delete|Contains a loop for deleting one row from a table containing 100 rows which were populated uing the t-SQL in CRUD Insert. Each iteration prepares and executes a statement. Measurement starts immediately before preparing the 1st DELETE statement and ends immediately after the 100th statement executes.|1000|100|DELETE TOP(1) FROM &lt;tableName&gt;|
|Large Read|Fetches one row at a time from a large prepopulated database until the whole result set is fetched. Measurement starts immediately before preparing the SELECT statement and ends immediately after the last fetch.|10,000,000|1|SELECT \* FROM &lt;tableName&gt;|
|Select @Version|Fetches the SQL Server version. Measurement starts immediately before executing the SELECT statement and ends immediately after fetch.|10,000|1|SELECT @@Version|N/A|
|Create database, table, procedure|Executes t-SQL statements to create a database, table, and procedure. Measurement starts immediately before executing the CREATE DATABASE statement and ends immediately after the DROP DATABASE statement.|1000|1|<ul><li>CREATE DATABASE &lt;dbName&gt;</li><li>USE &lt;dbName&gt;</li><li>CREATE TABLE &lt;tableName&gt; (&lt;params&gt;)</li><li>CREATE PROCEDURE &lt;procName&gt; @id INTEGER, @name VARCHAR(32) AS SET NOCOUNT ON; SELECT id, name, value FROM $databaseName.$tableName WHERE id = @id AND name = @name</li><li>USE MASTER; DROP DATABASE &lt;dbName&gt;</li></ul>|<ul><li>INT</li><li>VARCHAR(32)</li><li>INT</li><li>DATE</li><li>TIMESTAMP</li><li>TIME(7)</li></ul>|
|Connection|Connects and disconnects from the database, with and without connection pooling. Measurement starts immediately before connecting and ends immediately after disconnecting.|1000|1||N/A|
