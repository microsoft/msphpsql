<?php

use PDOSqlsrvPerfTest\PDOSqlsrvUtil;

class PDOConnectionBench{
    /*
    * Opens a connection and closes it immediately
    */
    public function benchConnectAndDisconnect(){
        $conn = PDOSqlsrvUtil::connect();
        PDOSqlsrvUtil::disconnect( $conn );
    }
}
?>
