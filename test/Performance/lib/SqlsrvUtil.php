<?php

namespace SqlsrvPerfTest;

class SqlsrvUtil
{
    
    public static $loopsPerCRUDIter = 100;
 
    public static function connect()
    {
        require dirname(__FILE__).DIRECTORY_SEPARATOR.'connect.php';
        $options = array( "Database"=>$database, "UID"=>$uid, "PWD"=>$pwd, "ConnectionPooling"=>$pooling, "MultipleActiveResultSets"=>$mars );
        $conn = sqlsrv_connect( $server, $options );
        if ( $conn === false )
        {
            die( print_r( sqlsrv_errors(), true));
        }
        return $conn;
    }

    public static function disconnect( $conn )
    {
        if ( $conn === false || $conn === null )
        {
            die( print_r( "Invalid connection resource\n"));
        }
        $ret = sqlsrv_close( $conn );
        if ( $ret === false )
        {
            die( print_r( sqlsrv_errors(), true));
        }
    }

    public static function selectVersion( $conn )
    {
        $sql = "SELECT @@Version";
        $stmt = self::query( $conn, $sql );
        return self::fetchArray( $stmt );
    }
    
    public static function createDbTableProc( $conn, $databaseName, $tableName, $procName )
    {
        $tableParams = "id INTEGER, name VARCHAR(32), value INTEGER, start_date DATE, time_added TIMESTAMP, set_time TIME(7)";
        $procParams = "@id INTEGER, @name VARCHAR(32)";
        $procTextBase = "SET NOCOUNT ON; SELECT id, name, value FROM $databaseName.$tableName WHERE id = @id AND name = @name";
        self::createDatabase( $conn, $databaseName );
        self::useDatabase( $conn, $databaseName );
        self::createTable( $conn, $tableName, $tableParams );
        self::createStoredProc( $conn, $procName, $procParams, $procTextBase );
    }
    
    public static function generateInsertValues()
    {
        $vcharVal = "test string";
        $nvcharVal = "wstring";
        $intVal = 3;
        $dateTimeVal = '2016-10-31 01:39:39.7341976';
        $charVal = "fixedstr";
        $ncharVal = "fixed w string";
        $realVal = 14.2;
        $binVal = 0x0123456789ABCDE;
        $vbinVal = 13;
        $dateTimeOffsetVal = '7032-12-17 02:32:18.5210310+00:00';
        $values = array( $vcharVal, $nvcharVal, $intVal, $dateTimeVal, $charVal, $ncharVal, $realVal, $binVal, $vbinVal, $dateTimeOffsetVal );
        return $values;    
    }

    public static function generateUpdateValues()
    {
        $vcharVal = "test string updated";
        $nvcharVal = "wstring updated";
        $intVal = 5;
        $dateTimeVal = '2005-10-31 01:20:39.7341976';
        $charVal = "fixedstr updated";
        $ncharVal = "fixed w string updated";
        $realVal = 19.2;
        $binVal = 0x01345789ABCDE;
        $vbinVal = 18;
        $dateTimeOffsetVal = '1032-12-17 02:42:18.5210310+00:00'; 
        $updatedValues = array( $vcharVal, $nvcharVal, $intVal, $dateTimeVal, $charVal, $ncharVal, $realVal, $binVal, $vbinVal, $dateTimeOffsetVal );
        return $updatedValues;
    }

    public static function generateUpdateParams()
    {
        $fieldNames = array(
                    "vstring",
                    "nvstring",
                    "num",
                    "dttwo",
                    "string",
                    "nstring",
                    "real",
                    "bin",
                    "vbin",
                    "dtoffset");

        $params = "";
        foreach( $fieldNames as $fieldName )
        {
            $params = $params.$fieldName."=?,";
        }
        $params = rtrim($params,", ");
        return $params;    
    }

    public static function insertWithPrepare( $conn, $tableName, $values )
    {
        $sql = "INSERT INTO $tableName VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = self::prepare( $conn, $sql, $values );
        self::execute( $stmt );
    }

    public static function updateWithPrepare( $conn, $tableName, $updateValues, $params )
    {
        $sql = "UPDATE $tableName SET ".$params;
        $stmt = self::prepare( $conn, $sql, $updateValues );
        self::execute( $stmt );
    }

    public static function fetchWithPrepare( $conn, $tableName )
    {
        $sql = "SELECT * FROM $tableName";
        $stmt = self::prepare( $conn, $sql, array());
        self::execute( $stmt );
        while( $row = self::fetchArray( $stmt ) ) {}
    }
    
    public static function createCRUDTable( $conn, $tableName )
    {
        $fields = array(
                    "vstring" => "VARCHAR(64)",
                    "nvstring" => "NVARCHAR(64)",
                    "num" => "int",
                    "dttwo" => "DATETIME2",
                    "string" => "CHAR(64)",
                    "nstring" => "NCHAR(64)",
                    "real" => "NUMERIC",
                    "bin" => "BINARY(16)",
                    "vbin" => "VARBINARY",
                    "dtoffset" => "DATETIMEOFFSET");
        $params = "";
        foreach( $fields as $fieldName => $type )
        {
            $params .= $fieldName." ".$type.",";
        }
        $params = rtrim($params,", ");
        self::createTable( $conn, $tableName, $params );
    }

    public static function query( $conn, $sql )
    {
        $stmt = sqlsrv_query( $conn, $sql );
        if( $stmt === false )
        {
            die( print_r( sqlsrv_errors(), true));
        }
        return $stmt;
    }

    public static function fetch( $stmt )
    {
        $ret = sqlsrv_fetch( $stmt );
        if( $ret === false )
        {
            die( print_r( sqlsrv_errors(), true));
        }
        return $ret;
    }

    public static function fetchArray( $stmt )
    {
        $row = sqlsrv_fetch_array( $stmt );
        if ( $row === false )
        {
            die( print_r( sqlsrv_errors(), true));
        }
        return $row;
    }

    public static function getField( $stmt, $index )
    {
        return sqlsrv_get_field( $stmt, $index );
    }

    private function createDatabase( $conn, $dbName )
    {
        $sql = "CREATE DATABASE $dbName";
        self::query( $conn, $sql );
    }

    public static function dropDatabase( $conn, $dbName )
    {
        $sql = "USE MASTER;DROP DATABASE $dbName";
        self::query( $conn, $sql );
    }

    public static  function createTable( $conn, $tableName, $params )
    {
        $sql = "CREATE TABLE $tableName ($params)";
        self::query( $conn, $sql );
    }

    public static function dropTable( $conn, $tableName )
    {
        $sql = "DROP TABLE $tableName";
        self::query( $conn, $sql );
    }

    private function useDatabase( $conn, $dbName )
    {
        $sql = "USE $dbName";
        self::query( $conn, $sql );
    }

    private function createStoredProc( $conn, $procName, $params, $text )
    {
        $sql = "CREATE PROCEDURE $procName $params AS $text";
        self::query( $conn, $sql );
    }

    private function dropStoredProc( $conn, $procName )
    {
        $sql = "DROP PROCEDURE $procName";
        self::query( $conn, $sql );
    }

    private function insert( $conn, $tableName, $values )
    {
        $sql = "INSERT INTO $tableName values ($values)";
        self::query( $conn, $sql );
    }

    private function update( $conn, $tableName, $params, $condition )
    {
        $sql = "UPDATE $tableName SET $params WHERE $condition";
        self::query( $sql );
    }
    
    public function delete( $conn, $tableName)
    {
        $sql = "DELETE TOP (1) FROM $tableName";
        self::query( $conn, $sql );
    }

    public function deleteWithPrepare( $conn, $tableName )
    {
        $sql = "DELETE TOP (1) FROM $tableName";
        $stmt = self::prepare( $conn, $sql, array() );
        self::execute( $stmt );
    }

    private function prepare( $conn, $sql, $params )
    {
        $stmt = sqlsrv_prepare( $conn, $sql, $params );
        if( $stmt === false )
        {
            die( print_r( sqlsrv_errors(), true));
        }
        return $stmt;
    }

    public function execute( $stmt )
    {
        $ret = sqlsrv_execute( $stmt );
        if ( $ret === false )
        {
            die( print_r( sqlsrv_errors(), true));
        }
    }
}
