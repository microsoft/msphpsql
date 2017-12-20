--TEST--
Test a complex query with IDENTITY_INSERT
--DESCRIPTION--
Verifies the behavior of INSERT queries with and without the IDENTITY flag set.
This test is similar to the other test TC33_ComplexQuery but using UTF-8 data
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
﻿<?php
require_once('MsCommon.inc');

function insertTest($conn, $tableName, $inputs, $query)
{
    $stmt = null;
    if (!AE\isColEncrypted()) {
        $insertSql = AE\getInsertSqlComplete($tableName, $inputs);
        $sql = str_replace("SQL", $insertSql, $query);
        $stmt = sqlsrv_query($conn, $sql);
    } else {
        // must bind parameters
        $insertSql = AE\getInsertSqlPlaceholders($tableName, $inputs);
        $params = array();
        foreach ($inputs as $key => $input) {
            array_push($params, $inputs[$key]);
        }
        // this contains a batch of sql statements, 
        // with set identity_insert on or off 
        // thus, sqlsrv_query should be called
        $sql = str_replace("SQL", $insertSql, $query);
        $stmt = sqlsrv_query($conn, $sql, $params);
    }

    return $stmt;
}

function complexQuery()
{
    set_time_limit(0);
    sqlsrv_configure('WarningsReturnAsErrors', 1);

    // Connect
    $conn = AE\connect(array("CharacterSet"=>"UTF-8"));

    $tableName = 'complex_query_test';
    $columns = array(new AE\ColumnMeta('int', 'c1_int', "IDENTITY"),
                     new AE\ColumnMeta('tinyint', 'c2_tinyint'),
                     new AE\ColumnMeta('smallint', 'c3_smallint'),
                     new AE\ColumnMeta('bigint', 'c4_bigint'),
                     new AE\ColumnMeta('varchar(512)', 'c5_varchar'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }
    sqlsrv_free_stmt($stmt);

    $noExpectedRows = 0;
    $noActualRows = 0;
    $stmt = sqlsrv_query($conn, "SET IDENTITY_INSERT $tableName ON;");
    sqlsrv_free_stmt($stmt);
    $noExpectedRows++;

    $inputs = array("c1_int" => 1324944463, "c2_tinyint" => 105, "c3_smallint" => 18521, "c4_bigint" => 2022363960, "c5_varchar" => "üv£ª©*@*rãCaC|/ä*,,@ý©bvªäîCUBão_+ßZhUªî¢~ÖÜ/ª@ä+ßßar~Özr,aß/,bCaü<ÖÐhÐbß<î/ðzãý+bÜ:Zßöüª@BÖUß<U@¢Ö<hÖhubÄrÐÃ*.å|a/,ª¢ßOa@oubýãýý£îZ~,ä¢î|+ª¢rZUCrOu,B£åß|:£ªîoBärÐA/BzOoß<bvu~ßuîCãß¢¢îýA@aðuAa,UÐ.>Ußaåab|¢ª¢|ü£/ÃßzzuªãA.ªZUöß<©a>OzübBüÜ|bZ./öbvß*rbö>ß©r//~ÖCÜhð¢bßz:¢Ä+_Ã__£ý£Uýh:v¢bý,©Ü£©,A:Zh>ßåvö+Ä>Ã.ßvC|:Ü*Üü*åz|b.©©üAý@uU.oOÜýAÜÐÜð©OB|rrîbU<övoUßäZÃÖ<ÄåªAÄ.Ua*ÖOªB,åîzB:ÜhövÖZhýðüC");
    $stmt = AE\insertRow($conn, $tableName, $inputs);
    unset($inputs);
    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_query($conn, "SET IDENTITY_INSERT $tableName OFF;");
    sqlsrv_free_stmt($stmt);
    $inputs = array("c1_int" => -1554807493, "c2_tinyint" => 253, "c3_smallint" => -12809, "c4_bigint" => -1698802997, "c5_varchar" => "ßöîÄ@v+oß+î|ruý*Ü¢ãÖ~.*ArABªßOBzUU£ßUuÜ<ðýr|b~_äaü/OÖzv.¢ä>>OÜ+¢vªzöªoB_ä+ßªrÜö£>U~ãÖð~ðýur,ÖvZh¢ªZ>vªUäåîz,>ÃräðäýðO_ä*a,ö+üÐß~bÃü¢<<+îýÐöäªO/zA+:îZ,öBÐü<î£îåhBÖzßÄ~,:>bð<~aÐãö¢*¢våvÃÐåî@a<vBãßÖäåª¢<üa.u:>_Äu£öa~våu>¢Bã©å:Aßã£Üvåö+aä£U<bUu*rv+@U_|ð@+v@Üßb|,.ªäÖ/*ÃªýÄ¢¢Ö/+ä><¢b@|zbãÖ@ÃãUb|ÄB£©,~ßð©ðUßöZÜöî£Zz<>åäZßð©ßaÖÖ¢bð£ßÄ>îÃÃ.~z>h_ý~ÜrüÖBruß+ª©CB©O>rå,Chro,£ßbZ_ß©,ÃUu|ßåüÄ/ý*åu|~Ö.ßZUoä:~A~CZhðU|öZ:ä/£Ä*î©ÄhävhbrzîÐ@.rãß©@uÜ©~>ÖÜööCÄzÜCü+>oZÄÜ/ABßA_b|b¢bÜh<|uBr.B*rü>aCª|AÄ©@öÖßÖ~Ö<rÐ,ä@©|££.C.üå¢/rbªßî");
    $stmt = AE\insertRow($conn, $tableName, $inputs);
    unset($inputs);

    $errors = sqlsrv_errors(SQLSRV_ERR_ALL);
    $count1 = count($errors);
    $e = $errors[0];
    $value1 = $e['message'];
    print "$value1\n";
    $value2 = $e['code'];
    print "$value2\n";
    $value3 = $e['SQLSTATE'];
    print "$value3\n";
    $noExpectedRows++;

    if (AE\isColEncrypted()) {
        // When AE is enabled, SQL types must be specified for sqlsrv_query
        $inputs = array("c1_int" => array(1994559593, null, null, SQLSRV_SQLTYPE_INT), 
                        "c2_tinyint" => array(129, null, null, SQLSRV_SQLTYPE_TINYINT),
                        "c3_smallint" => array(-8455, null, null, SQLSRV_SQLTYPE_SMALLINT),
                        "c4_bigint" => array(-236232445, null, null, SQLSRV_SQLTYPE_BIGINT), 
                        "c5_varchar" => array("ß£*ÐO+ö+<ã:>üoîzÄ¢zz~Ãýö|vUå>|CÄUü~>buÃv<ä~Ö+.ü*ªbuî_bBC©.oîCbåîÐÖUa~/U>öAäÐBu~ozîZ/zrOOä:ß©bßo.ü©A¢höÖoßÖü>r+A/ßaªrß:ª@|bhhªª/oå<Ö:rüa+oC¢~uÄü>/.ãbOöª_b@bbß¢|uzßªÖ¢~uäýub©ãaZäC£ÄrÖ,üöäu+Ãîö|||,U.BråãoýbüåöÃburöoî+>öä©î,u_öb©@C:ÜåÜîÜåAÖzýbð|Z<Ãý.£rîZ|/z@¢£AýZ,ßuZ*:b.AzÐ¢ä¢üßöbvbväð|<**~Uv.Ð*Ä©B*ýCUöa¢åO©Ãß*ÃÃ|ÜðA@îÃßaB<hÜîaZoöå>öüahUUA+ß£_u|~äö.©hr£oBo<äãüO+_å<OÐªÖßßväzA,~u~Obbî@ßÃãÜää©,.bO:C£Ü,äUO¢åå**hÐ~UZ©ðh<abß*üÖîC.äßh~Uð<r*ßäv£î*@¢Cv/BÖhAüB~ýAå@Z@<a_O|<©ßb*CZO,o:ã+¢£ÃZC©B¢o+>O:Z~Üoîßzb£ª£A.AÖÜÄ._O_å£ß", null, null, SQLSRV_SQLTYPE_VARCHAR(512)));
    } else {
        $inputs = array("c1_int" => 1994559593, "c2_tinyint" => 129, "c3_smallint" => -8455, "c4_bigint" => -236232445, "c5_varchar" => "ß£*ÐO+ö+<ã:>üoîzÄ¢zz~Ãýö|vUå>|CÄUü~>buÃv<ä~Ö+.ü*ªbuî_bBC©.oîCbåîÐÖUa~/U>öAäÐBu~ozîZ/zrOOä:ß©bßo.ü©A¢höÖoßÖü>r+A/ßaªrß:ª@|bhhªª/oå<Ö:rüa+oC¢~uÄü>/.ãbOöª_b@bbß¢|uzßªÖ¢~uäýub©ãaZäC£ÄrÖ,üöäu+Ãîö|||,U.BråãoýbüåöÃburöoî+>öä©î,u_öb©@C:ÜåÜîÜåAÖzýbð|Z<Ãý.£rîZ|/z@¢£AýZ,ßuZ*:b.AzÐ¢ä¢üßöbvbväð|<**~Uv.Ð*Ä©B*ýCUöa¢åO©Ãß*ÃÃ|ÜðA@îÃßaB<hÜîaZoöå>öüahUUA+ß£_u|~äö.©hr£oBo<äãüO+_å<OÐªÖßßväzA,~u~Obbî@ßÃãÜää©,.bO:C£Ü,äUO¢åå**hÐ~UZ©ðh<abß*üÖîC.äßh~Uð<r*ßäv£î*@¢Cv/BÖhAüB~ýAå@Z@<a_O|<©ßb*CZO,o:ã+¢£ÃZC©B¢o+>O:Z~Üoîßzb£ª£A.AÖÜÄ._O_å£ß");
    }
    $query = "SET IDENTITY_INSERT [$tableName] ON; SQL; SET IDENTITY_INSERT [$tableName] OFF;";
    $stmt = insertTest($conn, $tableName, $inputs, $query);
    print_r(sqlsrv_errors());
    unset($inputs);
    sqlsrv_free_stmt($stmt);

    echo "Number of rows inserted: $noExpectedRows\n";

    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");
    while ($result = sqlsrv_fetch($stmt)) {
        $noActualRows++;
    }
    sqlsrv_free_stmt($stmt);

    echo "Number of rows fetched: $noActualRows\n";

    if ($noActualRows != $noExpectedRows) {
        echo("Number of rows does not match expected value\n");
    }
    
    dropTable($conn, $tableName);
    sqlsrv_close($conn);
}

echo "\nTest begins...\n";

try {
    complexQuery();
} catch (Exception $e) {
    echo $e->getMessage();
}
echo "\nDone\n";

?>
--EXPECTREGEX--
﻿
Test begins\.\.\.
\[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Cannot insert explicit value for identity column in table '.+' when IDENTITY_INSERT is set to OFF\.
544
23000
Number of rows inserted: 2
Number of rows fetched: 2

Done
