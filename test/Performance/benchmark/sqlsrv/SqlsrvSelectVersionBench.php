<?php

use SqlsrvPerfTest\SqlsrvUtil;
/**
 * @Iterations(10000)
 * @BeforeMethods({"connect"})
 * @AfterMethods({"disconnect"})
 */
class SqlsrvSelectVersionBench
{
 
    private $conn;
   
    public function connect()
    {
        $this->conn = SqlsrvUtil::connect();
    }
    /*
    * Each iteration calls execDirect API to fetch @@Version
    */
    public function benchSelectVersion()
    {
        $version = SqlsrvUtil::selectVersion( $this->conn );
    }

    public function disconnect()
    {
        SqlsrvUtil::disconnect( $this->conn );
    }
}
