<?php

use SqlsrvPerfTest\SqlsrvUtil;
include_once __DIR__ . "/../../lib/CRUDBaseBenchmark.php";
/**
 * @BeforeMethods({"connect", "setTableName", "createTable", "generateInsertValues", "insertWithPrepare"})
 * @AfterMethods({ "dropTable", "disconnect"})
 */
class SqlsrvFetchBench extends CRUDBaseBenchmark 
{

    private $conn;
    private $tableName;
    private $insertValues;

    public function setTableName()
    {
        $this->tableName = "datatypes_".rand();
    }

    public function connect()
    {
        $this->conn = SqlsrvUtil::connect();
    }

    public function createTable()
    {
        SqlsrvUtil::createCRUDTable( $this->conn, $this->tableName );
    }

    public function generateInsertValues()
    {
        $this->insertValues = SqlsrvUtil::generateInsertValues();
    }

    public function insertWithPrepare()
    {
        SqlsrvUtil::insertWithPrepare( $this->conn, $this->tableName, $this->insertValues );
    }

    /**
     * Each iteration inserts a row into the table, benchFetchWithPrepare() fetches that row 1000 times.
     * Note that, every fetch calls prepare, execute and fetch APIs.
     */
    public function benchFetchWithPrepare()
    {
        for( $i=0; $i<SqlsrvUtil::$loopsPerCRUDIter; $i++)
        {
            SqlsrvUtil::fetchWithPrepare( $this->conn, $this->tableName );
        }
    }

    public function dropTable()
    {
        SqlsrvUtil::dropTable( $this->conn, $this->tableName );
    }

    public function disconnect()
    {
        SqlsrvUtil::disconnect( $this->conn );
    }
}
