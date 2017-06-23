--TEST--
Test PDO::prepare() with PDO::ATTR_EMULATE_PREPARES.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

require_once 'MsCommon.inc';

$db = connect();

$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

// retrieve correct results
$s = $db->prepare( "SELECT '' + TITLE FROM cd_info GROUP BY '' + TITLE" );

$s->execute();

$titles = array();

while( $r = $s->fetch()) {

    $titles[] = $r[0];   
}

$exception_thrown = false;

try {

$s = $db->prepare( 'SELECT :prefix + TITLE FROM cd_info GROUP BY :prefix + TITLE' );

$s->bindValue( ':prefix', "" );

$s->execute();

while( $r = $s->fetch()) {
   
    print_r( $r );
}

}
catch ( PDOException $e ) {

    $exception_thrown = true;
}

if( !$exception_thrown ) {

    die( "Exception not thrown\nTest failed\n" );
}

$s = $db->prepare( "SELECT :prefix + TITLE FROM cd_info GROUP BY :prefix + TITLE",
                   array( PDO::ATTR_EMULATE_PREPARES => true ));

$s->bindValue( ':prefix', "" );

$s->execute();

$param_titles = array();

while( $r = $s->fetch()) {
   
    $param_titles[] = $r[0];   
}

if ( $titles === $param_titles ) {
    echo "Test succeeded\n";
}
else {

    echo "Test failed\n";
    print_r( $titles );
    print_r( $param_titles );
}

?>
--EXPECT--
Test succeeded
