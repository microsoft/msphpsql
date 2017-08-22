<?php

use SqlsrvPerfTest\SqlsrvUtil;
include_once __DIR__ . "/../../lib/CRUDBaseBenchmark.php";
/**
 * @BeforeMethods({"connect", "setTableName", "createTable", "generateInsertValues", "insertWithPrepare"})
 * @AfterMethods({  "dropTable", "disconnect"})
 */
class SqlsrvDeleteBench extends CRUDBaseBenchmark 
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
        for ( $i=0; $i<SqlsrvUtil::$loopsPerCRUDIter; $i++ )
        {
            SqlsrvUtil::insertWithPrepare( $this->conn, $this->tableName, $this->insertValues );
        }
    }
    /**
     * Each iteration inserts 1000 rows into the table, benchDelete deletes top row from the table 1000 times.
     * Note that, every delete calls prepare and execute APIs.
     */    
    public function benchDelete()
    {
        for( $i=0; $i<SqlsrvUtil::$loopsPerCRUDIter; $i++ )
        {
            SqlsrvUtil::delete( $this->conn, $this->tableName );
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
