<?php

use SqlsrvPerfTest\SqlsrvUtil;
include_once __DIR__ . "/../../lib/CRUDBaseBenchmark.php";

class SqlsrvConnectionBench extends CRUDBaseBenchmark 
{
    /*
    * Opens a connection and closes it immediately
    */
    public function benchConnectAndDisconnect()
    {
        $conn = SqlsrvUtil::connect();
        SqlsrvUtil::disconnect( $conn );
    }
}
