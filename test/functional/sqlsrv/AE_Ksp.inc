<?php

function getKSPpath() 
{
    $name = 'myKSP';

    $dir_name = realpath(dirname(__FILE__));
    $ksp = $dir_name . DIRECTORY_SEPARATOR . $name;
    if ( strtoupper( substr( php_uname( 's' ), 0, 3 ) ) == 'WIN' ) {
        $arch = 'x64';
        if ( PHP_INT_SIZE == 4 ) // running 32 bit
            $arch = '';
        $ksp .= $arch . '.dll';
    }
    else
        $ksp .= '.so';
        
    return $ksp;
}       

$ksp_name = 'MyCustomKSPName';
$encrypt_key = 'LPKCWVD07N3RG98J0MBLG4H2';
$ksp_test_table = 'CustomKSPTestTable';

?>