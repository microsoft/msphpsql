--TEST--
PDO Interface Test
--DESCRIPTION--
Verifies the compliance of the PDO API Interface.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function ConnInfo()
{
    include 'MsSetup.inc';

    $testName = "PDO - Interface";
    StartTest($testName);

    $conn1 = Connect();

    CheckInterface($conn1);
    $conn1 = null;

    EndTest($testName);
}

function CheckInterface($conn)
{
    $expected = array(
        'getAvailableDrivers'   => true,
        '__construct'       => true,
        'errorCode'     => true,
        'errorInfo'     => true,
        'getAttribute'      => true,
        'setAttribute'      => true,
        'beginTransaction'  => true,
        'commit'        => true,
        'rollBack'      => true,
        'exec'          => true,
        'query'         => true,
        'prepare'       => true,
        'lastInsertId'      => true,
        'quote'         => true,
        '__wakeup'      => true,
        '__sleep'       => true,
        'inTransaction'     => true,
    );
    $classname = get_class($conn);
    $methods = get_class_methods($classname);
    foreach ($methods as $k => $method)
    {
        if (isset($expected[$method]))
        {
            unset($expected[$method]);
            unset($methods[$k]);
        }
        if ($method == $classname)
        {
            unset($expected['__construct']);
            unset($methods[$k]);
        }
    }
    if (!empty($expected))
    {
        printf("Dumping missing class methods\n");
        var_dump($expected);
    }
    if (!empty($methods))
    {
        printf("Found more methods than expected, dumping list\n");
        var_dump($methods);
    }
}


//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{

    try
    {
        ConnInfo();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "PDO - Interface" completed successfully.