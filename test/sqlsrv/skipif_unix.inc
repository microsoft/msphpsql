<?php

if ( !( strtoupper( substr( php_uname( 's' ),0,3 ) ) === 'WIN' ) ) die( "Skip, test on windows only." ); 

if (!extension_loaded("sqlsrv")) {
    die("skip extension not loaded");
}

?>