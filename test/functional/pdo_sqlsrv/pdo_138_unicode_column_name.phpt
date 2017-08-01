--TEST--
Github 138. Test for Unicode Column Metadata.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

class MyStatement extends PDOStatement {
    public function BindValues(array &$values, array &$blobs, $placeholder_prefix, $columnInformation, &$max_placeholder = NULL, $blob_suffix = NULL) {
        if (empty($max_placeholder)) {
            $max_placeholder = 0;
        }
        foreach ($values as $field_name => &$field_value) {
            $placeholder = $placeholder_prefix . $max_placeholder++;
            $blob_key = $placeholder . $blob_suffix;
            if (isset($columnInformation['blobs'][$field_name])) {
                $blobs[$blob_key] = fopen('php://memory', 'a');
                fwrite($blobs[$blob_key], $field_value);
                rewind($blobs[$blob_key]);
                $this->bindParam($placeholder, $blobs[$blob_key], PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
            }
            else {
                // Even though not a blob, make sure we retain a copy of these values.
                $blobs[$blob_key] = $field_value;
                $this->bindParam($placeholder, $blobs[$blob_key], PDO::PARAM_STR);
            }
        }
    }
}


/**
 *
 * @param string $connection_id
 *
 * @return PDO
 */
function connection($connection_id) {
    include 'MsSetup.inc';
    $host = $server;
    $database =  $databaseName;
    $username =  $uid;
    $password =  $pwd;

    static $connections = array();
    if (!isset($connections[$connection_id])) {
        $connection_options['pdo'] = array();
        $connection_options['pdo'][PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;

        $cnn = new PDO("sqlsrv:server=$host;Database=$database",  $username, $password, $connection_options['pdo']);
        $cnn->setAttribute(PDO::ATTR_STATEMENT_CLASS, [MyStatement::class]);
        $connections[$connection_id] = $cnn;
    }
    return $connections[$connection_id];
}

/**
 * Summary of prepare
 *
 * @param mixed $connection
 * @param mixed $query
 * @return PDOStatement
 */
function prepare($connection, $query) {
    $pdo_options = array();
    $pdo_options[PDO::ATTR_EMULATE_PREPARES] = TRUE;
    $pdo_options[PDO::SQLSRV_ATTR_DIRECT_QUERY] = TRUE;
    $pdo_options[PDO::ATTR_CURSOR] = PDO::CURSOR_SCROLL;
    $pdo_options[PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE] = PDO::SQLSRV_CURSOR_BUFFERED;
    return $connection->prepare($query, $pdo_options);
}

/**
 * Summary of execute
 *
 * @param PDO $connection
 * @param string $query
 *
 * @param PDOStatement;
 */
function execute($connection, $query, array $args = array()) {
    $st = prepare($connection, $query);
    foreach ($args as $key => $value) {
        if (is_numeric($value)) {
            $st->bindValue($key, $value, PDO::PARAM_INT);
        }
        else {
            $st->bindValue($key, $value, PDO::PARAM_STR);
        }
    }

    $st->execute();

    // Bind column types properly.
    $null = array();
    $st->columnNames = array();
    for ($i = 0; $i < $st->columnCount(); $i++) {
        $meta = $st->getColumnMeta($i);
        $st->columnNames[]= $meta['name'];
        $sqlsrv_type = $meta['sqlsrv:decl_type'];
        $parts = explode(' ', $sqlsrv_type);
        $type = reset($parts);
        switch($type) {
            case 'varbinary':
                $null[$i] = NULL;
                $st->bindColumn($i + 1, $null[$i], PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
                break;
            case 'int':
            case 'bit':
            case 'smallint':
            case 'tinyint':
            case 'bigint':
                $null[$i] = NULL;
                $st->bindColumn($i + 1, $null[$i], PDO::PARAM_INT);
                break;
        }
    }

    return $st;
}


//*******************************************************
// TEST BEGIN
//*******************************************************

$connection = connection('default');

// Drop
try {
    execute($connection, 'DROP TABLE [mytáble]');
}
catch(Exception $e) {}

$tablescript = <<<EOF

CREATE TABLE [dbo].[mytáble](
	[id] [nchar](10) NULL,
	[väriable] [nchar](10) NULL,
	[tésting] [nchar](10) NULL
) ON [PRIMARY]

EOF;

// Recreate
execute($connection, $tablescript);

$query = <<<EOF
INSERT INTO [mytáble] (id, tésting, väriable) VALUES (:db_insert0, :db_insert1, :db_insert2)
EOF;

$blobs = [];

/** @var MyStatement */
$st = prepare($connection, $query);

$st->bindValue(':db_insert0', 'a', PDO::PARAM_STR);
$st->bindValue(':db_insert1', 'b', PDO::PARAM_STR);
$st->bindValue(':db_insert2', 'c', PDO::PARAM_STR);

$st->execute();

$st = prepare($connection, 'SELECT * FROM [mytáble]');

$st->execute();

while($row = $st->fetchAll()){
    $row = reset($row);
    echo (isset($row['id']) ? "OK" : "FAIL") , "\n";
    echo (isset($row['tésting']) ? "OK" : "FAIL") , "\n";
    echo (isset($row['väriable']) ? "OK" : "FAIL") , "\n";
}

for ($i = 0; $i < $st->columnCount(); $i++) {
    $meta = $st->getColumnMeta($i);
    echo $meta['name'] , "\n";
}
?>

--EXPECT--
OK
OK
OK
id
väriable
tésting