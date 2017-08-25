<?php

use PDOSqlsrvPerfTest\PDOSqlsrvUtil;
include_once __DIR__ . "/../../lib/CRUDBaseBenchmark.php";
/**
 * @BeforeMethods({"connect", "setTableName", "createTable", "generateInsertValues", "generateUpdateValues", "generateUpdateParams"})
 * @AfterMethods({ "dropTable", "disconnect"})
 */
class PDOSqlsrvCRUDBench extends CRUDBaseBenchmark 
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
    
    public function generateUpdateValues()
    {
        $this->updateValues = PDOSqlsrvUtil::generateUpdateValues();
    }

    public function generateUpdateParams()
    {
        $this->updateParams = PDOSqlsrvUtil::generateUpdateParams();
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
        for( $i=0; $i<PDOSqlsrvUtil::$loopsPerCRUDIter; $i++ )
        {
            PDOSqlsrvUtil::insertWithPrepare( $this->conn, $this->tableName, $this->insertValues );
            PDOSqlsrvUtil::fetchWithPrepare( $this->conn, $this->tableName );
            PDOSqlsrvUtil::updateWithPrepare( $this->conn, $this->tableName, $this->updateValues, $this->updateParams );
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
