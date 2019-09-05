--TEST--
Test that right braces are escaped correctly and that error messages are correct when they're not
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
$server = 'fakeserver';
$uid = 'sa';
$password = 'fakepassword';

// If the braces are fine, then we expect the connection to fail with a login timeout error
$braceError = "An unescaped right brace (}) was found";
$connError = (strtoupper(substr(php_uname('s'), 0, 3)) === 'WIN') ? "Could not open a connection to SQL Server" : "Login timeout expired";

// Every combination of one, two, three, or more right braces I can think of
$testStrings = array(array("}", $braceError),
                     array("{", $connError),
                     array("{t}", $connError),
                     array("{}}", $braceError),
                     array("}}", $connError),
                     array("}}}", $braceError),
                     array("}}}}", $connError),
                     array("{}}}", $connError),
                     array("}{", $braceError),
                     array("}{{", $braceError),
                     array("test", $connError),
                     array("{test}", $connError),
                     array("{test", $connError),
                     array("test}", $braceError),
                     array("{{test}}", $braceError),
                     array("{{test}", $connError),
                     array("{{test", $connError),
                     array("test}}", $connError),
                     array("{test}}", $braceError),
                     array("test}}}", $braceError),
                     array("{test}}}", $connError),
                     array("{test}}}}", $braceError),
                     array("{test}}}}}", $connError),
                     array("{test}}}}}}", $braceError),
                     array("te}st", $braceError),
                     array("{te}st}", $braceError),
                     array("{te}}st}", $connError),
                     array("{te}}}st}", $braceError),
                     array("te}}s}t", $braceError),
                     array("te}}s}}t", $connError),
                     array("te}}}st", $braceError),
                     array("te}}}}st", $connError),
                     array("tes}}t", $connError),
                     array("te}s}}t", $braceError),
                     array("tes}}t}}", $connError),
                     array("tes}}t}}}", $braceError),
                     array("tes}t}}", $braceError),
                     );

foreach ($testStrings as $test) {

    try {
        $conn = new PDO("sqlsrv:Server=".$server.";LoginTimeout=1;", $test[0], $password);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), $test[1]) === false) {
            print_r("Wrong error message returned for test string ".$test[0].". Expected ".$test[1].", actual output:\n");
            print_r($e->getMessage);
            echo "\n";
        } 
    }
}

echo "Done.\n";
?>
--EXPECT--
Done.
