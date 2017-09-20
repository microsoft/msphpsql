<?php

use PDOSqlsrvPerfTest\PDOSqlsrvUtil;
include_once __DIR__ . "/../../lib/CRUDBaseBenchmark.php";
/**
 * @BeforeMethods({"connect"})
 * @AfterMethods({"disconnect"})
 */
class PDOCreateDbTableProcBench extends CRUDBaseBenchmark 
{
    private $conn;

    public function connect()
    {
        $this->conn = PDOSqlsrvUtil::connect();
    }
    /*
    * Each iteration creates a database, a table and a stored procedure in that database and drops the database at the end.
    * Note that, execDirect function are used to execute all the queries. 
    */
    public function benchCreateDbTableProc()
    {
        $randomNum = rand();
        $databaseName = "test_db_$randomNum";
        $tableName = "test_table_$randomNum";
        $procName = "test_proc_$randomNum";
        PDOSqlsrvUtil::createDbTableProc( $this->conn, $databaseName, $tableName, $procName );
        PDOSqlsrvUtil::dropDatabase( $this->conn, $databaseName );
    }

    public function disconnect()
    {
        PDOSqlsrvUtil::disconnect( $this->conn );
    }
}
