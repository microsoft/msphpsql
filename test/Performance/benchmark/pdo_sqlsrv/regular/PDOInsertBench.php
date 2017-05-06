<?php

use PDOSqlsrvPerfTest\PDOSqlsrvUtil;
/**
 * @BeforeMethods({"connect", "setTableName", "createTable", "generateInsertValues"})
 * @AfterMethods({ "dropTable", "disconnect"})
 */
class PDOInsertBench{

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
    /**
     * Each iteration inserts 1000 rows into the table.
     * Note that, every insertion calls prepare, bindParam and execute APIs.
     */
    public function benchInsertWithPrepare(){
        for ( $i=0; $i<1000; $i++){
            PDOSqlsrvUtil::insertWithPrepare( $this->conn, $this->tableName, $this->insertValues );
        }
    }

    public function dropTable(){
        PDOSqlsrvUtil::dropTable( $this->conn, $this->tableName );
    }

    public function disconnect(){
        PDOSqlsrvUtil::disconnect( $this->conn );
    }
}
