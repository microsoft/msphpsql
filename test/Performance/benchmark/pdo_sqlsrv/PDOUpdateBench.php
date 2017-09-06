<?php

use PDOSqlsrvPerfTest\PDOSqlsrvUtil;
include_once __DIR__ . "/../../lib/CRUDBaseBenchmark.php";
/**
 * @BeforeMethods({"connect", "setTableName", "createTable", "generateInsertValues", "insertWithPrepare", "generateUpdateValues", "generateUpdateParams"})
 * @AfterMethods({"dropTable", "disconnect"})
 */
class PDOUpdateBench extends CRUDBaseBenchmark 
{

    private $conn;
    private $tableName;
    private $insertValues;
    private $updateValues;
    private $updateParams;

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
        PDOSqlsrvUtil::insertWithPrepare( $this->conn, $this->tableName, $this->insertValues );
    }
    
    public function generateUpdateValues()
    {
        $this->updateValues = PDOSqlsrvUtil::generateUpdateValues();
    }

    public function generateUpdateParams()
    {
        $this->updateParams = PDOSqlsrvUtil::generateUpdateParams();
    }
    /**
     * Each iteration inserts a row into the table, updateWithPrepare() updates that row 1000 times.
     * Note that, every update calls prepare, bindParam and execute APIs.
     */
    public function benchUpdateWithPrepare()
    {
        for( $i=0; $i<PDOSqlsrvUtil::$loopsPerCRUDIter; $i++ )
        {
            PDOSqlsrvUtil::updateWithPrepare( $this->conn, $this->tableName, $this->updateValues, $this->updateParams );
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
