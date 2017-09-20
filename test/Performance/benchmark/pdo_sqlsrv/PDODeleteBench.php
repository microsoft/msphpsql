<?php

use PDOSqlsrvPerfTest\PDOSqlsrvUtil;
include_once __DIR__ . "/../../lib/CRUDBaseBenchmark.php";
/**
 * @BeforeMethods({"connect", "setTableName", "createTable", "generateInsertValues", "insertWithPrepare"})
 * @AfterMethods({ "dropTable","disconnect"})
 */
class PDODeleteBench extends CRUDBaseBenchmark 
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
        $this->conn = PDOSqlsrvUtil::connect();
    }

    public function createTable()
    {
        PDOSqlsrvUtil::createCRUDTable( $this->conn, $this->tableName );
    }

    public function generateInsertValues()
    {
        $this->insertValues = PDOSqlsrvUtil::generateInsertValues();
    }

    public function insertWithPrepare()
    {
        for( $i=0; $i<PDOSqlsrvUtil::$loopsPerCRUDIter; $i++ )
        {
            PDOSqlsrvUtil::insertWithPrepare( $this->conn, $this->tableName, $this->insertValues );
        }
    }
    /**
     * Each iteration inserts 1000 rows into the table, benchDelete deletes top row from the table 1000 times.
     * Note that, every delete calls prepare and execute APIs.
     */        
    public function benchDelete()
    {
        for( $i=0; $i<PDOSqlsrvUtil::$loopsPerCRUDIter; $i++ )
        {
            PDOSqlsrvUtil::deleteWithPrepare( $this->conn, $this->tableName );
        }
    }

    public function dropTable()
    {
        PDOSqlsrvUtil::dropTable( $this->conn, $this->tableName );
    }

    public function disconnect()
    {
        PDOSqlsrvUtil::disconnect( $this->conn );
    }

}
