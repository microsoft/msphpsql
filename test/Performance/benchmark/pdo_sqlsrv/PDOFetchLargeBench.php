<?php

use PDOSqlsrvPerfTest\PDOSqlsrvUtil;
/**
 * @Iterations(1)
 * @BeforeMethods({"connect", "setTableName" })
 * @AfterMethods({ "disconnect"})
 */
class PDOFetchLargeBench
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
        $this->conn = PDOSqlsrvUtil::connect();
    }
    /*
    * Each iteration calls prepare, execute and fetch APIs to fetch the already populated data
    */
    public function benchFetchWithPrepare()
    {
        PDOSqlsrvUtil::fetchWithPrepare( $this->conn, $this->tableName );
    }

    public function disconnect()
    {
        PDOSqlsrvUtil::disconnect( $this->conn );
    }
}
