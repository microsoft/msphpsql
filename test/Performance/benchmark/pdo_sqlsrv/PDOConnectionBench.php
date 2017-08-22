<?php

use PDOSqlsrvPerfTest\PDOSqlsrvUtil;
include_once __DIR__ . "/../../lib/CRUDBaseBenchmark.php";

class PDOConnectionBench extends CRUDBaseBenchmark 
{
    /*
    * Opens a connection and closes it immediately
    */
    public function benchConnectAndDisconnect()
    {
        $conn = PDOSqlsrvUtil::connect();
        PDOSqlsrvUtil::disconnect( $conn );
    }
}
?>
