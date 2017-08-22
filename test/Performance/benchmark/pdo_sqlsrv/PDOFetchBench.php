<?php

use PDOSqlsrvPerfTest\PDOSqlsrvUtil;
/**
 * @Iterations(1000)
 * @BeforeMethods({"connect", "setTableName", "createTable", "generateInsertValues", "insertWithPrepare"})
 * @AfterMethods({"dropTable", "disconnect"})
 */
class PDOFetchBench{

    private $conn;
    private $tableName;
    private $insertValues;

    public function setTableName(){
        $this->tableName = "datatypes_".rand();
    }
    public function connect(){
        $this->conn = PDOSqlsrvUtil::connect();
    }

    public function createTable(){
        PDOSqlsrvUtil::createCRUDTable( $this->conn, $this->tableName );
    }

    public function generateInsertValues(){
        $this->insertValues = PDOSqlsrvUtil::generateInsertValues();
    }

    public function insertWithPrepare(){
        PDOSqlsrvUtil::insertWithPrepare( $this->conn, $this->tableName, $this->insertValues );
    }

    /**
     * Each iteration inserts a row into the table, benchFetchWithPrepare() fetches that row 1000 times.
     * Note that, every fetch calls prepare, execute and fetch APIs.
     */    
    public function benchFetchWithPrepare(){
        for( $i=0; $i<100; $i++){
            PDOSqlsrvUtil::fetchWithPrepare( $this->conn, $this->tableName );
        }
    }

    public function dropTable(){
        PDOSqlsrvUtil::dropTable( $this->conn, $this->tableName );
    }

    public function disconnect(){
        PDOSqlsrvUtil::disconnect( $this->conn );
    }
}