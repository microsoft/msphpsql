--TEST--
zombied streams after sqlsrv_stmt_cancel.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

    require( 'MsCommon.inc' );

    $conn = Connect();
    if( !$conn ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_connect failed." );
    }

    $stmt = sqlsrv_query( $conn, "SELECT review1 FROM cd_info" );
    if( $stmt == false ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_query failed." );
    }
    
    sqlsrv_fetch( $stmt );
    $stream = sqlsrv_get_field( $stmt, 0, SQLSRV_PHPTYPE_STREAM("binary"));
    while( !feof( $stream )) { 
        $str = fread( $stream, 80 );
        echo "$str\n";
    }

    sqlsrv_fetch( $stmt );
    $stream = sqlsrv_get_field( $stmt, 0, SQLSRV_PHPTYPE_STREAM("binary"));
    $str = fread( $stream, 80 );
    echo "$str\n";
    sqlsrv_cancel( $stmt );
    while( !feof( $stream ) && is_resource($stream)) { 
        $str = fread( $stream, 80 );
        echo "$str\n";
    }
    sqlsrv_free_stmt( $stmt );

    $stmt = sqlsrv_query( $conn, "SELECT review1 FROM cd_info" );
    // the fread causes a Function Sequence error in ODBC and doesn't work at all.
    sqlsrv_fetch( $stmt );
    $stream = sqlsrv_get_field( $stmt, 0, SQLSRV_PHPTYPE_STREAM("binary"));
    sqlsrv_cancel( $stmt );
    while( !feof( $stream ) && is_resource($stream) ) { 
        $str = fread( $stream, 80 );
        echo "$str\n";
    }
    
    sqlsrv_free_stmt( $stmt );
    sqlsrv_close( $conn );

?>
--EXPECTREGEX--
Source: Amazon.com \- As it turned out, Led Zeppelins infamous 1969 debut album w
as indicative of the decade to come--one that, fittingly, this band helped defin
e with its decadently exaggerated, bowdlerized blues-rock. In shrieker Robert Pl
ant, ex-Yardbird Jimmy Page found a vocalist who could match his guitar pyrotech
nics, and the band pounded out its music with swaggering ferocity and Richter-sc
ale-worthy volume. Pumping up blues classics such as Otis Rushs I Cant Quit You 
Baby and Howlin Wolfs How Many More Times into near-cartoon parodies, the band a
lso hinted at things to come with the manic Communication Breakdown and the lumb
ering set stopper Dazed and Confused. \<I\>--Billy Altman\<\/I\>
Source: Amazon.com essential recording - Most critics complain \<I\>Back in Black\<

Warning: feof\(\): supplied resource is not a valid stream resource in .+(\/|\\)0022\.php on line [0-9]+

Warning: feof\(\): supplied resource is not a valid stream resource in .+(\/|\\)0022\.php on line [0-9]+
