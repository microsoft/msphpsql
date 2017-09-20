<?php

if(! extension_loaded( "sqlsrv" ) ) 
    die( "skip extension not loaded" );

require_once( "MsCommon.inc" );
$conn = Connect();
if( ! $conn )
{
    echo( "Error: could not connect during SKIPIF!" );
}
else if(! IsAEQualified( $conn ) )
{
    die( "skip - AE feature not supported in the current environment." );
}  
?>