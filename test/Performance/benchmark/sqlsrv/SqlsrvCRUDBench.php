<?php

use SqlsrvPerfTest\SqlsrvUtil;
include_once __DIR__ . "/../../lib/CRUDBaseBenchmark.php";
/**
 * @BeforeMethods({"connect", "setTableName", "createTable", "generateInsertValues", "generateUpdateValues", "generateUpdateParams"})
 * @AfterMethods({ "dropTable", "disconnect"})
 */
class SqlsrvCRUDBench extends CRUDBaseBenchmark 
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
    
    public function generateUpdateValues()
    {
        $this->updateValues = SqlsrvUtil::generateUpdateValues();
    }

    public function generateUpdateParams()
    {
        $this->updateParams = SqlsrvUtil::generateUpdateParams();
    }
    /**
     * Each iteration does the following $loopsPerCRUDIter times:
     *    (i) insert a row into the table with insertWithPrepare
     *   (ii) fetch the row with fetchWithPrepare
     *  (iii) update the row's contents with updateWithPrepare
     *   (iv) delete the row with delete
     * Every insertion calls prepare, bindParam and execute APIs.
     * Every fetch calls prepare, execute and fetch APIs.
     * Every update calls prepare, bindParam and execute APIs.
     * Every delete calls prepare and execute APIs.
     */    
    public function benchCRUDWithPrepare()
    {
        for( $i=0; $i<SqlsrvUtil::$loopsPerCRUDIter; $i++ )
        {
            SqlsrvUtil::insertWithPrepare( $this->conn, $this->tableName, $this->insertValues );
            SqlsrvUtil::fetchWithPrepare( $this->conn, $this->tableName );
            SqlsrvUtil::updateWithPrepare( $this->conn, $this->tableName, $this->updateValues, $this->updateParams );
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
