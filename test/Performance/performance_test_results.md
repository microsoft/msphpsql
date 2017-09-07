# Performance Test Results for the Microsoft Drivers for PHP for SQL Server

The following table lists benchmarking results for both the SQLSRV and PDO_SQLSRV drivers for various operations and various environments. All tests have been executed using the 64-bit non-thread-safe drivers with PHP 7.1.7, as appropriate for the client platform. The server testing environment is either Windows Server 2016 with SQL Server 2016, or Ubuntu 16.04 with SQL Server Linux 2016.
|||||SQLSRV|||PDO_SQLSRV|||
|:---|:---|:---|:---|---:|---:|:---|---:|---:|
|Test|Server|Client|Iterations|Memory(MB)|Time (s)|Iterations|Memory(MB)|Time (s)|
|Run 1000 CRUD operations for n iterations|SQL Server Windows|Windows Server 2016|1000x100 rows|0.88667|1074|1000x100 rows|0.88562|1195 |
|||OS X Sierra|||||||
|||Red Hat 7.2|||||||
|||Ubuntu 16.04|||||||
|||Suse 12|1000x100 rows|0.88945|978|1000x100 rows|0.88849|1084|
||SQL Server Linux|Windows Server 2016|||||||
|||OS X Sierra|||||||
|||Red Hat 7.2|||||||
|||Ubuntu 16.04|||||||
|||Suse 12|||||||
|Run 1000 INSERT operation for n iterations|SQL Server Windows|Windows|1000x100 rows|0.8838|311|1000x100 rows|0.88264|319|
|||OS X Sierra|1000x100 rows|0.8877|280|1000x100 rows|0.8866|282|
|||Red Hat 7.2|1000x100 rows|0.88342|304|1000x100 rows|0.88229|309|
|||Ubuntu 16.04|1000x100 rows|0.88171|309|1000x100 rows|0.88065|357|
|||Suse 12|1000x100 rows|0.88655|285|1000x100 rows|0.88548|290|
||SQL Server Linux|Windows|1000x100 rows|0.88681|368|1000x100 rows|0.88568|387|
|||OS X|1000x100 rows|0.8877|348|1000x100 rows|0.8866|359|
|||Red Hat 7.2|1000x100 rows|0.88342|349|1000x100 rows|0.8823|358|
|||Ubuntu 16.04|1000x100 rows|0.88179|357|1000x100 rows|0.88067|363|
|||Suse 12|1000x100 rows|0.88655|342|1000x100 rows|0.88548|344|
| Run 1000 FETCH operation for n iterations |SQL Server Windows|Windows|1000x100 rows|0.88431|255|1000x100 rows|0.88318|250|
|||OS X|1000x100 rows|0.88828|188|1000x100 rows|0.88718|187|
|||Red Hat 7.2|1000x100 rows|0.88396|248|1000x100 rows|0.88284|245|
|||Ubuntu 16.04|1000x100 rows|0.88226|250|1000x100 rows|0.87514|247|
|||Suse 12|1000x100 rows|0.88709|228|1000x100 rows|0.88602|227|
||SQL Server Linux|Windows|1000x100 rows|0.88735|287|1000x100 rows|0.88622|282|
|||OS X|1000x100 rows|0.88828|229|1000x100 rows|0.88718|228|
|||Red Hat 7.2|1000x100 rows|0.884|272|1000x100 rows|0.88287|270|
|||Ubuntu 16.04|1000x100 rows|0.88236|276|1000x100 rows|0.88124|274|
|||Suse 12|1000x100 rows|0.88709|257|1000x100 rows|0.88602|254|
| Run 1000 UPDATE operation for n iterations |SQL Server Windows|Windows|1000x100 rows|0.88594|314|1000x100 rows|0.88478|323|
|||OS X|1000x100 rows|0.88988|284|1000x100 rows|0.88879|285|
|||Red Hat 7.2|1000x100 rows|0.8856|307|1000x100 rows|0.88448|313|
|||Ubuntu 16.04|1000x100 rows|0.87787|310|1000x100 rows|0.88235|327|
|||Suse 12|1000x1000 rows|0.88869|288|1000x1000 rows|0.88762|294|
||SQL Server Linux|Windows|1000x100 rows|0.88899|369|1000x100 rows|0.88786|376|
|||OS X|1000x100 rows|0.88988|349|1000x100 rows|0.88879|362|
|||Red Hat 7.2|1000x100 rows|0.8856|357|1000x100 rows|0.88448|362|
|||Ubuntu 16.04|1000x100 rows|0.88397|361|1000x100 rows|0.88285|367|
|||Suse 12|1000x100 rows|0.88873|343|1000x100 rows|0.88766|340|
|Run 1000 DELETE operation for n iterations  |SQL Server Windows|Windows|1000x100 rows|0.88452|197|1000x100 rows|0.88341|303|
|||OS X|1000x100 rows|0.88843|188|1000x100 rows|0.88737|274|
|||Red Hat 7.2|1000x100 rows|0.88411|192|1000x100 rows|0.88303|295|
|||Ubuntu 16.04|1000x100 rows|0.87638|193|1000x100 rows|0.88094|298|
|||Suse 12|1000x1000 rows|0.88728|181|1000x1000 rows|0.88625|277|
||SQL Server Linux|Windows|1000x100 rows|0.88754|233|1000x100 rows|0.88645|358|
|||OS X|1000x100 rows|0.88843|243|1000x100 rows|0.88737|340|
|||Red Hat 7.2|1000x100 rows|0.88415|229|1000x100 rows|0.88307|342|
|||Ubuntu 16.04|1000x100 rows|0.88251|229|1000x100 rows|0.88144|347|
|||Suse 12|1000x100 rows|0.88728|220|1000x100 rows|0.88625|328|
|Run READ of a large dataset - 10,000,000 rows|SQL Server Windows|Windows|1|0.88117|606|1|0.88003|382|
|||OS X|1|0.88572|420|1|0.88463|344|
|||Red Hat 7.2|1|0.88145|495|1|0.88035|694|
|||Ubuntu 16.04|1|0.87981|413|1|0.87259|585|
|||Suse 12|1|0.88397|453|1|0.88291|326|
||SQL Server Linux|Windows|1|0.88423|685||||
|||OS X|1|0.88571|308||||
|||Red Hat 7.2|1|0.8814|512||||
|||Ubuntu 16.04|1|0.87977|749||||
|||Suse 12|1|0.88397|828||||
|Run SELECT @@Version for n iterations|SQL Server Windows|Windows|10000|0.88074|16|10000|0.87955|28|
|||OS X|10,000|0.88522|19|10,000|0.88412|33|
|||Red Hat 7.2|10,000|0.88118|11|1000x10|0.88006|22|
|||Ubuntu 16.04|||||||
|||Suse 12|10,000|0.88351|13|1000x10|0.88243|24|
||SQL Server Linux|Windows|10,000|0.88377|16|10,000|0.88264|30|
|||OS X|10,000|0.88522|19|10,000|0.88412|33|
|||Red Hat 7.2|10,000|0.88094|17|10,000|0.87981|28|
|||Ubuntu 16.04|10,000|0.8793|16|10,000|0.87818|26|
|||Suse 12|10,000|0.88351|15|10,000|0.88243|26|
|Run CREATE DATABASE/TABLE/STORED PROCS for n iterations|SQL Server Windows|Windows|1000|0.88237|515|1000|0.88123|529|
|||OS X|1000|0.88638|420|1000|0.8853|457|
|||Red Hat 7.2|1000|0.88213|434|1000|0.88102|447|
|||Ubuntu 16.04|1000|0.87437|395||||
|||Suse 12|1000|0.88512|494|1000|0.88406|507|
||SQL Server Linux|Windows|1000|0.88544|2285|1000|0.88433|2324|
|||OS X|1000|0.88638|2338|1000|0.8853|2352|
|||Red Hat 7.2|1000|0.88216|2292|1000|0.88106|2312|
|||Ubuntu 16.04|1000|0.8053|2340|1000|0.87943|1228|
|||Suse 12|1000|0.88512|2341|1000|0.88406|2337|
|Open 1000 connections and close 1000 connections|SQL Server Windows|Windows|1000|0.87956|39|1000|0.87843|38|
|||OS X|1000|0.88363|62|1000|0.88255|62|
|||Red Hat 7.2|1000|0.87935|30|1000|0.87825|30|
|||Ubuntu 16.04||||1000|0.87608|29|
|||Suse 12|1000|0.88232|24|1000|0.88126|25|
||SQL Server Linux|Windows|1000|0.88267|45|1000|0.88156|45|
|||OS X|1000|0.88363|65|1000|0.88255|65|
|||Red Hat 7.2|1000|0.87932|35|1000|0.87822|35|
|||Ubuntu 16.04|1000|0.87771|35|1000|0.87659|34|
|||Suse 12|1000|0.88232|29|1000|0.88126|30|
|Open 1000 connections and close 1000 connections with connection pooling|SQL Server Windows|Windows||||||                       |
|||OS X|||||||
|||Red Hat 7.2|||||||
|||Ubuntu 16.04|||||||
|||Suse 12|||||||
||SQL Server Linux|Windows|||||||
|||OS X|||||||
|||Red Hat 7.2|||||||
|||Ubuntu 16.04|||||||
|||Suse 12|||||||
