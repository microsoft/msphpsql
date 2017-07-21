<?php

use SqlsrvPerfTest\SqlsrvUtil;

class SqlsrvConnectionBench{
    /*
    * Opens a connection and closes it immediately
    */
    public function benchConnectAndDisconnect(){
        $conn = SqlsrvUtil::connect();
        SqlsrvUtil::disconnect( $conn );
    }
}
