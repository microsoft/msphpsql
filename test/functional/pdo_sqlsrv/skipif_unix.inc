<?php

if ( !( strtoupper( substr( php_uname( 's' ),0,3 ) ) === 'WIN' ) ) die( "Skip, test on windows only." ); 

if (!extension_loaded("pdo") || !extension_loaded('pdo_sqlsrv'))
    die("PDO driver cannot be loaded; skipping test.\n");

?>