<?php

use SqlsrvPerfTest\SqlsrvUtil;
include_once __DIR__ . "/../../lib/CRUDBaseBenchmark.php";
/**
 * @BeforeMethods({"connect", "setTableName", "createTable", "generateInsertValues", "insertWithPrepare", "generateUpdateValues", "generateUpdateParams"})
 * @AfterMethods({ "dropTable", "disconnect"})
 */
class SqlsrvUpdateBench extends CRUDBaseBenchmark 
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
    
    public function generateUpdateValues()
    {
        $this->updateValues = SqlsrvUtil::generateUpdateValues();
    }

    public function generateUpdateParams()
    {
        $this->updateParams = SqlsrvUtil::generateUpdateParams();
    }
    /**
     * Each iteration inserts a row into the table, updateWithPrepare() updates that row 1000 times.
     * Note that, every update calls prepare, bindParam and execute APIs.
     */
    public function benchUpdateWithPrepare()
    {
        for( $i=0; $i<SqlsrvUtil::$loopsPerCRUDIter; $i++ )
        {
            SqlsrvUtil::updateWithPrepare( $this->conn, $this->tableName, $this->updateValues, $this->updateParams );
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
