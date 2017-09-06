<?php

use SqlsrvPerfTest\SqlsrvUtil;
/**
 * @Iterations(1)
 * @BeforeMethods({"connect", "setTableName" })
 * @AfterMethods({ "disconnect"})
 */
class SqlsrvFetchLargeBench
{

    private $conn;
    private $tableName;

    public function setTableName()
    {
        //Assumes the table is already populated with data
        $this->tableName = "LargeDB.dbo.datatypes";
    }

    public function connect()
    {
        $this->conn = SqlsrvUtil::connect();
    }
    /*
    * Each iteration calls prepare, execute and fetch APIs to fetch the already populated data
    */
    public function benchFetchWithPrepare()
    {
        SqlsrvUtil::fetchWithPrepare( $this->conn, $this->tableName );
    }

    public function disconnect()
    {
        SqlsrvUtil::disconnect( $this->conn );
    }
}
