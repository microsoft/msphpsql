<?php

use SqlsrvPerfTest\SqlsrvUtil;
/**
 * @BeforeMethods({"connect"})
 * @AfterMethods({"disconnect"})
 */
class SqlsrvSelectVersionBench{
 
    private $conn;
   
    public function connect(){
        $this->conn = SqlsrvUtil::connect();
    }
    /*
    * Each iteration calls execDirect API to fetch @@Version
    */
    public function benchSelectVersion(){
        for( $i=0; $i<10; $i++ ){
            $version = SqlsrvUtil::selectVersion( $this->conn );
        }
    }

    public function disconnect(){
        SqlsrvUtil::disconnect( $this->conn );
    }
}
