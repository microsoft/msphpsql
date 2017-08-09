<?php

use PDOSqlsrvPerfTest\PDOSqlsrvUtil;
/**
 * @Iterations(1000)
 */
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
