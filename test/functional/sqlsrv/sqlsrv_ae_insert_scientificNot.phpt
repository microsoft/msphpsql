--TEST--
Test for inserting into and retrieving from decimal columns of different scale
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsHelper.inc');

$posExp = array("-0.00e+01", "10.0E+00", "-1.333e+1", "1.9178464696202265E+2", "-8.3333e+2", "8.5000000000000006E+2", "-8.5164835164835168E+2", "3.16E+05", "-5E+05", "1.53502e+006", "-7.5013e+006", "7.54001e+006", "-7.54045e+006", "820.0E+10", "-1.12255E+7", "1.23456789E+9", "-1.23456789012346E+7", "1.377532E+10", "-5.368709185426E04", "+.9999E3");
$negExp = array("0.00e-01", "-10.0E-00", "1.333e-1", "-1.9178464696202265E-2", "8.3333e-2", "-8.5000000000000006E-2", "8.5164835164835168E-2", "-3.16E-01", "5E-03", "-1.53502e-004", "7.5013e-004", "-7.54001e-004", "7.54045e-004", "-820.0E-1", "1.12255E-4", "-1.23456789E-3", "1.23456789012346E-4", "-1.377532E-1", "5369709.185426e-08", " .9999e-3");
$numSets = array("Testing numbers greater than 1 or less than -1:" => $posExp,
                 "Testing numbers between 1 and -1:" => $negExp);
$scalesToTest = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 19);

function testErrorCases($conn)
{
    // Create a dummy table 
    $tableName = "srv_sci_not";
    $colMeta = array(new AE\ColumnMeta("decimal(38, 1)", "Column1"));
    
    AE\createTable($conn, $tableName, $colMeta);

    $expected = '*Invalid character value for cast specification';
    $tsql = "INSERT INTO $tableName (Column1) VALUES (?)";   
    $input = "- 0E1.3";
    $param = array(
        array(&$input, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_DECIMAL(38, 1))
        );
    
    $stmt = sqlsrv_prepare($conn, $tsql, $param);
    if (!sqlsrv_execute($stmt)) {
        if (!fnmatch($expected, sqlsrv_errors()[0]['message'])) {
            var_dump(sqlsrv_errors());
        }
    } else {
        echo "Expect $input to fail";
    }  
    
    $input = "8e0-2";
    if (!sqlsrv_execute($stmt)) {
        if (!fnmatch($expected, sqlsrv_errors()[0]['message'])) {
            var_dump(sqlsrv_errors());
        }
    } else {
        echo "Expect $input to fail";
    }     

    $input = "-19e032+"; 
    if (!sqlsrv_execute($stmt)) {
        if (!fnmatch($expected, sqlsrv_errors()[0]['message'])) {
            var_dump(sqlsrv_errors());
        }
    } else {
        echo "Expect $input to fail";
    }     
    dropTable($conn, $tableName);
}

try {
    $conn = AE\connect();
    
    testErrorCases($conn);
    
    $tbname = "decimalTable";
    foreach ($numSets as $testName => $numSet) {
        echo "\n$testName\n";
        foreach ($numSet as $input) {
            $numInt = ceil(log10(abs($input) + 1));
            $decimalTypes = array();
            foreach ($scalesToTest as $scale) {
                if ($scale < 39 - $numInt) {
                    array_push($decimalTypes, new AE\ColumnMeta("decimal(38, $scale)", "c$scale"));
                }
            }
            if (empty($decimalTypes)) {
                $decimalTypes = array(new AE\ColumnMeta("decimal(38, 0)", "c0"));
            }
            AE\createTable($conn, $tbname, $decimalTypes);

            $insertValues = array();
            foreach ($decimalTypes as $decimalType) {
                $scale = intval(ltrim($decimalType->colName, "c"));
                array_push($insertValues, array($input, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_DECIMAL(38, $scale)));
            }

            $insertSql = "INSERT INTO $tbname VALUES(" . AE\getSeqPlaceholders(count($insertValues)) . ")";
            $stmt = sqlsrv_prepare($conn, $insertSql, $insertValues);
            sqlsrv_execute($stmt);

            $stmt = sqlsrv_query($conn, "SELECT * FROM $tbname");
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            foreach ($row as $key => $value) {
                if ($value != 0) {
                    echo "$key: $value\n";
                }
            }
            sqlsrv_query($conn, "TRUNCATE TABLE $tbname");
        }
    }
    dropTable($conn, $tbname);
    sqlsrv_close($conn);
} catch (PDOException $e) {
    echo $e->getMessage();
}

?>
--EXPECT--

Testing numbers greater than 1 or less than -1:
c0: 10
c1: 10.0
c2: 10.00
c3: 10.000
c4: 10.0000
c5: 10.00000
c6: 10.000000
c7: 10.0000000
c8: 10.00000000
c9: 10.000000000
c19: 10.0000000000000000000
c0: -13
c1: -13.3
c2: -13.33
c3: -13.330
c4: -13.3300
c5: -13.33000
c6: -13.330000
c7: -13.3300000
c8: -13.33000000
c9: -13.330000000
c19: -13.3300000000000000000
c0: 192
c1: 191.8
c2: 191.78
c3: 191.785
c4: 191.7846
c5: 191.78465
c6: 191.784647
c7: 191.7846470
c8: 191.78464696
c9: 191.784646962
c19: 191.7846469620226500000
c0: -833
c1: -833.3
c2: -833.33
c3: -833.330
c4: -833.3300
c5: -833.33000
c6: -833.330000
c7: -833.3300000
c8: -833.33000000
c9: -833.330000000
c19: -833.3300000000000000000
c0: 850
c1: 850.0
c2: 850.00
c3: 850.000
c4: 850.0000
c5: 850.00000
c6: 850.000000
c7: 850.0000000
c8: 850.00000000
c9: 850.000000000
c19: 850.0000000000000600000
c0: -852
c1: -851.6
c2: -851.65
c3: -851.648
c4: -851.6484
c5: -851.64835
c6: -851.648352
c7: -851.6483516
c8: -851.64835165
c9: -851.648351648
c19: -851.6483516483516800000
c0: 316000
c1: 316000.0
c2: 316000.00
c3: 316000.000
c4: 316000.0000
c5: 316000.00000
c6: 316000.000000
c7: 316000.0000000
c8: 316000.00000000
c9: 316000.000000000
c19: 316000.0000000000000000000
c0: -500000
c1: -500000.0
c2: -500000.00
c3: -500000.000
c4: -500000.0000
c5: -500000.00000
c6: -500000.000000
c7: -500000.0000000
c8: -500000.00000000
c9: -500000.000000000
c19: -500000.0000000000000000000
c0: 1535020
c1: 1535020.0
c2: 1535020.00
c3: 1535020.000
c4: 1535020.0000
c5: 1535020.00000
c6: 1535020.000000
c7: 1535020.0000000
c8: 1535020.00000000
c9: 1535020.000000000
c19: 1535020.0000000000000000000
c0: -7501300
c1: -7501300.0
c2: -7501300.00
c3: -7501300.000
c4: -7501300.0000
c5: -7501300.00000
c6: -7501300.000000
c7: -7501300.0000000
c8: -7501300.00000000
c9: -7501300.000000000
c19: -7501300.0000000000000000000
c0: 7540010
c1: 7540010.0
c2: 7540010.00
c3: 7540010.000
c4: 7540010.0000
c5: 7540010.00000
c6: 7540010.000000
c7: 7540010.0000000
c8: 7540010.00000000
c9: 7540010.000000000
c19: 7540010.0000000000000000000
c0: -7540450
c1: -7540450.0
c2: -7540450.00
c3: -7540450.000
c4: -7540450.0000
c5: -7540450.00000
c6: -7540450.000000
c7: -7540450.0000000
c8: -7540450.00000000
c9: -7540450.000000000
c19: -7540450.0000000000000000000
c0: 8200000000000
c1: 8200000000000.0
c2: 8200000000000.00
c3: 8200000000000.000
c4: 8200000000000.0000
c5: 8200000000000.00000
c6: 8200000000000.000000
c7: 8200000000000.0000000
c8: 8200000000000.00000000
c9: 8200000000000.000000000
c19: 8200000000000.0000000000000000000
c0: -11225500
c1: -11225500.0
c2: -11225500.00
c3: -11225500.000
c4: -11225500.0000
c5: -11225500.00000
c6: -11225500.000000
c7: -11225500.0000000
c8: -11225500.00000000
c9: -11225500.000000000
c19: -11225500.0000000000000000000
c0: 1234567890
c1: 1234567890.0
c2: 1234567890.00
c3: 1234567890.000
c4: 1234567890.0000
c5: 1234567890.00000
c6: 1234567890.000000
c7: 1234567890.0000000
c8: 1234567890.00000000
c9: 1234567890.000000000
c19: 1234567890.0000000000000000000
c0: -12345679
c1: -12345678.9
c2: -12345678.90
c3: -12345678.901
c4: -12345678.9012
c5: -12345678.90123
c6: -12345678.901235
c7: -12345678.9012346
c8: -12345678.90123460
c9: -12345678.901234600
c19: -12345678.9012346000000000000
c0: 13775320000
c1: 13775320000.0
c2: 13775320000.00
c3: 13775320000.000
c4: 13775320000.0000
c5: 13775320000.00000
c6: 13775320000.000000
c7: 13775320000.0000000
c8: 13775320000.00000000
c9: 13775320000.000000000
c19: 13775320000.0000000000000000000
c0: -53687
c1: -53687.1
c2: -53687.09
c3: -53687.092
c4: -53687.0919
c5: -53687.09185
c6: -53687.091854
c7: -53687.0918543
c8: -53687.09185426
c9: -53687.091854260
c19: -53687.0918542600000000000
c0: 1000
c1: 999.9
c2: 999.90
c3: 999.900
c4: 999.9000
c5: 999.90000
c6: 999.900000
c7: 999.9000000
c8: 999.90000000
c9: 999.900000000
c19: 999.9000000000000000000

Testing numbers between 1 and -1:
c0: -10
c1: -10.0
c2: -10.00
c3: -10.000
c4: -10.0000
c5: -10.00000
c6: -10.000000
c7: -10.0000000
c8: -10.00000000
c9: -10.000000000
c19: -10.0000000000000000000
c1: .1
c2: .13
c3: .133
c4: .1333
c5: .13330
c6: .133300
c7: .1333000
c8: .13330000
c9: .133300000
c19: .1333000000000000000
c2: -.02
c3: -.019
c4: -.0192
c5: -.01918
c6: -.019178
c7: -.0191785
c8: -.01917846
c9: -.019178465
c19: -.0191784646962022650
c1: .1
c2: .08
c3: .083
c4: .0833
c5: .08333
c6: .083333
c7: .0833330
c8: .08333300
c9: .083333000
c19: .0833330000000000000
c1: -.1
c2: -.09
c3: -.085
c4: -.0850
c5: -.08500
c6: -.085000
c7: -.0850000
c8: -.08500000
c9: -.085000000
c19: -.0850000000000000060
c1: .1
c2: .09
c3: .085
c4: .0852
c5: .08516
c6: .085165
c7: .0851648
c8: .08516484
c9: .085164835
c19: .0851648351648351680
c1: -.3
c2: -.32
c3: -.316
c4: -.3160
c5: -.31600
c6: -.316000
c7: -.3160000
c8: -.31600000
c9: -.316000000
c19: -.3160000000000000000
c2: .01
c3: .005
c4: .0050
c5: .00500
c6: .005000
c7: .0050000
c8: .00500000
c9: .005000000
c19: .0050000000000000000
c4: -.0002
c5: -.00015
c6: -.000154
c7: -.0001535
c8: -.00015350
c9: -.000153502
c19: -.0001535020000000000
c3: .001
c4: .0008
c5: .00075
c6: .000750
c7: .0007501
c8: .00075013
c9: .000750130
c19: .0007501300000000000
c3: -.001
c4: -.0008
c5: -.00075
c6: -.000754
c7: -.0007540
c8: -.00075400
c9: -.000754001
c19: -.0007540010000000000
c3: .001
c4: .0008
c5: .00075
c6: .000754
c7: .0007540
c8: .00075405
c9: .000754045
c19: .0007540450000000000
c0: -82
c1: -82.0
c2: -82.00
c3: -82.000
c4: -82.0000
c5: -82.00000
c6: -82.000000
c7: -82.0000000
c8: -82.00000000
c9: -82.000000000
c19: -82.0000000000000000000
c4: .0001
c5: .00011
c6: .000112
c7: .0001123
c8: .00011226
c9: .000112255
c19: .0001122550000000000
c3: -.001
c4: -.0012
c5: -.00123
c6: -.001235
c7: -.0012346
c8: -.00123457
c9: -.001234568
c19: -.0012345678900000000
c4: .0001
c5: .00012
c6: .000123
c7: .0001235
c8: .00012346
c9: .000123457
c19: .0001234567890123460
c1: -.1
c2: -.14
c3: -.138
c4: -.1378
c5: -.13775
c6: -.137753
c7: -.1377532
c8: -.13775320
c9: -.137753200
c19: -.1377532000000000000
c1: .1
c2: .05
c3: .054
c4: .0537
c5: .05370
c6: .053697
c7: .0536971
c8: .05369709
c9: .053697092
c19: .0536970918542600000
c3: .001
c4: .0010
c5: .00100
c6: .001000
c7: .0009999
c8: .00099990
c9: .000999900
c19: .0009999000000000000
