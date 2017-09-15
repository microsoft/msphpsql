<?php

use SqlsrvPerfTest\SqlsrvUtil;
include_once __DIR__ . "/../../lib/CRUDBaseBenchmark.php";
/**
 * @BeforeMethods({"connect"})
 * @AfterMethods({"disconnect"})
 */
class SqlsrvCreateDbTableProcBench extends CRUDBaseBenchmark 
{
    private $conn;

    public function connect()
    {
        $this->conn = SqlsrvUtil::connect();
    }

    /*
    * Each iteration creates a database, a table and a stored procedure in that database and drops the database at the end.
    * Note that, ODBC SQLExecDirect function are used to execute all the queries. 
    */
    public function benchCreateDbTableProc()
    {
        $randomNum = rand();
        $databaseName = "test_db_$randomNum";
        $tableName = "test_table_$randomNum";
        $procName = "test_proc_$randomNum";
        SqlsrvUtil::createDbTableProc( $this->conn, $databaseName, $tableName, $procName );
        SqlsrvUtil::dropDatabase( $this->conn, $databaseName );
    }

    public function disconnect()
    {
        SqlsrvUtil::disconnect( $this->conn );
    }
}
