--TEST--
Extending PDO Test #1
--DESCRIPTION--
Verification of capabilities for extending PDO.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function Extend()
{
    include 'MsSetup.inc';

    $testName = "PDO - Extension";
    StartTest($testName);

    // simply use $databaseName from MsSetup.inc to facilitate testing in Azure,  
    // which does not support switching databases
    $conn2 = new ExPDO("sqlsrv:Server=$server;Database=$databaseName", $uid, $pwd);
    DropTable($conn2, "tmp_table");
    $conn2->exec("CREATE TABLE tmp_table (id INT)");
    $conn2->exec("INSERT INTO tmp_table (id) VALUES (1), (2)");
    $stmt1 = $conn2->query("SELECT * FROM tmp_table ORDER BY id ASC");
    var_dump($stmt1->fetchAll(PDO::FETCH_ASSOC));
    var_dump($stmt1->fetch());
    $conn2->intercept_call();

    // Cleanup
    DropTable($conn2, "tmp_table");
    $stmt1 = null;
    $conn2 = null;

    EndTest($testName);
}

class ExPDO extends PDO
{
    public function __construct()
    {
        $this->protocol();
        $args = func_get_args();
        return (call_user_func_array(array($this, 'parent::__construct'), $args));
    }

    public function exec($args1)
    {
        $this->protocol();
        $args = func_get_args();
        return (call_user_func_array(array($this, 'parent::exec'), $args));
    }

    public function query()
    {
        $this->protocol();
        $args = func_get_args();
        return (call_user_func_array(array($this, 'parent::query'), $args));
    }

    public function __call($method, $args)
    {
        print "__call(".var_export($method, true).", ".var_export($args, true).")\n";
    }

    private function protocol()
    {
        $stack = debug_backtrace();
        if (!isset($stack[1]))
        {
            return;
        }
        printf("%s(", $stack[1]['function']);
        $args = '';
        foreach ($stack[1]['args'] as $k => $v)
        {
            $args .= sprintf("%s, ", var_export($v, true));
        }
        if ($args != '')
        {
            printf("%s", substr($args, 0, -2));
        }
        printf(")\n");
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
        Extend();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECTF--
__construct('%s', '%s', '%s')
exec('IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(%s) AND type in (N\'U\')) DROP TABLE %s')
exec('CREATE TABLE %s (id INT)')
exec('INSERT INTO %s (id) VALUES (1), (2)')
query('SELECT * FROM %s ORDER BY id ASC')
array(2) {
  [0]=>
  array(1) {
    ["id"]=>
    string(1) "1"
  }
  [1]=>
  array(1) {
    ["id"]=>
    string(1) "2"
  }
}
bool(false)
__call('intercept_call', array (
))
exec('IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(%s) AND type in (N\'U\')) DROP TABLE %s')
Test "PDO - Extension" completed successfully.