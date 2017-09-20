<?php

use SqlsrvPerfTest\SqlsrvUtil;
include_once __DIR__ . "/../../lib/CRUDBaseBenchmark.php";
/**
 * @BeforeMethods({"connect", "setTableName", "createTable", "generateInsertValues"})
 * @AfterMethods({"dropTable","disconnect"})
 */
class SqlsrvInsertBench extends CRUDBaseBenchmark 
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
    /**
     * Each iteration inserts 1000 rows into the table.
     * Note that, every insertion calls prepare, bindParam and execute APIs.
     */
    public function benchInsertWithPrepare()
    {
        for( $i=0; $i<SqlsrvUtil::$loopsPerCRUDIter; $i++ )
        {
            SqlsrvUtil::insertWithPrepare( $this->conn, $this->tableName, $this->insertValues );
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
