<?php

namespace PDOSqlsrvPerfTest;
use PDO;
class PDOSqlsrvUtil
{
    
    public static $loopsPerCRUDIter = 100;
    
    public static function connect()
    {
        require dirname(__FILE__).DIRECTORY_SEPARATOR.'connect.php';
        try
        {
            $conn = new PDO( "sqlsrv:Server=$server; Database=$database; ConnectionPooling=$pooling; MultipleActiveResultSets=$mars" , $uid, $pwd );       
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        }
        catch( PDOException $e )
        {
            var_dump( $e );
            exit;
        }
    }

    public static function disconnect( $conn )
    {
        $conn = null;
    }

    public static function selectVersion( $conn )
    {
        $sql = "SELECT @@Version";
        $stmt = self::query( $conn, $sql );
        return self::fetch( $stmt );
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
        $stmt = self::prepare( $conn, $sql );
        self::bindParams( $stmt, $values );
        self::execute( $stmt );
    }
    
    public static function fetchWithPrepare( $conn, $tableName )
    {
        $sql = "SELECT * FROM $tableName";
        $stmt = self::prepare( $conn, $sql );
        self::execute( $stmt );
        while ( $row = self::fetch( $stmt )){}   
    }

    public static function deleteWithPrepare( $conn, $tableName )
    {
        $sql = "DELETE TOP (1) FROM $tableName";
        $stmt = self::prepare( $conn, $sql );
        self::execute( $stmt );
    }

    public static function updateWithPrepare( $conn, $tableName, $updateValues, $params )
    {
        $sql = "UPDATE $tableName SET ".$params;
        $stmt = self::prepare( $conn, $sql );
        self::bindParams( $stmt, $updateValues );
        self::execute( $stmt );
    }

    private function bindParams( $stmt, $values )
    {
        //This functionn assumes the fields are from createCRUDTable()
        self::bindParam( $stmt, 1, $values[0], PDO::PARAM_STR);
        self::bindParam( $stmt, 2, $values[1], PDO::PARAM_STR);
        self::bindParam( $stmt, 3, $values[2], PDO::PARAM_INT);
        self::bindParam( $stmt, 4, $values[3], PDO::PARAM_STR);
        self::bindParam( $stmt, 5, $values[4], PDO::PARAM_STR);
        self::bindParam( $stmt, 6, $values[5], PDO::PARAM_STR);
        self::bindParam( $stmt, 7, $values[6], PDO::PARAM_STR);
        self::bindParam( $stmt, 8, $values[7], PDO::PARAM_LOB);
        self::bindParam( $stmt, 9, $values[8], PDO::PARAM_LOB);
        self::bindParam( $stmt, 10, $values[9], PDO::PARAM_STR);
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

    private function createDatabase( $conn, $dbName )
    {
        $sql = "CREATE DATABASE $dbName";
        $conn->exec( $sql );
    }

    public static function dropDatabase( $conn, $dbName )
    {
        $sql = "USE MASTER;DROP DATABASE $dbName";
        $conn->exec( $sql );
    }

    public static function createTable( $conn, $tableName, $params )
    {
        $sql = "CREATE TABLE $tableName ($params)";
        $conn->exec( $sql );
    }

    public static function dropTable( $conn, $tableName )
    {
        $sql = "DROP TABLE $tableName";
        $conn->exec( $sql );
    }

    private function useDatabase( $conn, $dbName )
    {
        $sql = "USE $dbName";
        $conn->exec( $sql );
    }

    private function createStoredProc( $conn, $procName, $params, $text )
    {
        $sql = "CREATE PROCEDURE $procName $params AS $text";
        $conn->exec( $sql );
    }

    private function dropStoredProc( $conn, $procName )
    {
        $sql = "DROP PROCEDURE $procName";
        $conn->exec( $sql );
    }

    private function query( $conn, $sql )
    {
        try
        {
            return $conn->query( $sql );
        }
        catch( PDOException $e )
        {
            var_dump( $e );
            exit;
        }
    }

    private function fetch( $stmt )
    {
        return $stmt->fetch();
    }

    private function prepare( $conn, $sql )
    {
        try
        {
            $stmt = $conn->prepare( $sql );
            if( $stmt === false )
            {
                die( "Failed to prepare\n");
            }
            return $stmt;
        }
        catch( PDOException $e )
        {
            var_dump( $e );
            exit;
        }
    }
    
    private function execute( $stmt )
    {
        $ret = $stmt->execute();
        if( $ret === false )
        {
            die( "Failed to execute\n" );
        }
    }

    private function bindParam( $stmt, $index, $value, $type )
    {
        $ret = $stmt->bindParam( $index, $value, $type );
        if ( $ret === false)
        {
            die( "Faild to bind\n");
        }
    }
}
