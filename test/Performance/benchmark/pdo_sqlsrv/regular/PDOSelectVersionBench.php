<?php

use PDOSqlsrvPerfTest\PDOSqlsrvUtil;
/**
 * @BeforeMethods({"connect"})
 * @AfterMethods({"disconnect"})
 */
class PDOSelectVersionBench{
 
    private $conn;

    public function connect(){
        $this->conn = PDOSqlsrvUtil::connect();
    }
    /*
    * Each iteration calls execDirect API to fetch @@Version
    */
    public function benchSelectVersion(){
        for( $i=0; $i<10; $i++ ){
            $version = PDOSqlsrvUtil::selectVersion( $this->conn );
        }
    }

    public function disconnect(){
        PDOSqlsrvUtil::disconnect( $this->conn );
    }
}
