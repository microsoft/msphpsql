<?php

use PDOSqlsrvPerfTest\PDOSqlsrvUtil;
/**
 * @Iterations(10000)
 * @BeforeMethods({"connect"})
 * @AfterMethods({"disconnect"})
 */
class PDOSelectVersionBench
{
 
    private $conn;

    public function connect()
    {
        $this->conn = PDOSqlsrvUtil::connect();
    }
    /*
    * Each iteration calls execDirect API to fetch @@Version
    */
    public function benchSelectVersion()
    {
        $version = PDOSqlsrvUtil::selectVersion( $this->conn );
    }

    public function disconnect()
    {
        PDOSqlsrvUtil::disconnect( $this->conn );
    }
}
