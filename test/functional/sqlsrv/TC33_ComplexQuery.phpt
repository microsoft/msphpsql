--TEST--
Complex Query Test
--DESCRIPTION--
Verifies the behavior of INSERT queries with and without the IDENTITY flag set.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php

require_once('MsCommon.inc');

function complexQuery()
{
    $testName = "Statement - Complex Query";
    startTest($testName);

    setup();
    $conn1 = AE\connect();

    $tableName = 'TC33test';
    $columns = array(new AE\ColumnMeta('int', 'c1_int', "IDENTITY"),
                     new AE\ColumnMeta('tinyint', 'c2_tinyint'),
                     new AE\ColumnMeta('smallint', 'c3_smallint'),
                     new AE\ColumnMeta('bigint', 'c4_bigint'),
                     new AE\ColumnMeta('varchar(512)', 'c5_varchar'));
    AE\createTable($conn1, $tableName, $columns);

    // SET IDENTITY_INSERT ON/OFF only works at execute or run time and not at parse time
    // because a prepared statement runs in a separate context
    // https://technet.microsoft.com/en-us/library/ms188059(v=sql.110).aspx
    $query = "SET IDENTITY_INSERT [$tableName] ON;";
    $stmt = sqlsrv_query($conn1, $query);
    if (!$stmt) {
        die("Unexpected execution outcome for \'$query\'.");
    }
    
    // expect this to pass
    $inputs = array("c1_int" => -204401468, "c2_tinyint" => 168, "c3_smallint" => 4787, "c4_bigint" =>1583186637, "c5_varchar" => "î<ÄäC~zããa.Oa._ß£*©<u_ßßCÃoa äãäĞßa+OühäobUa:zB_CÖ@~UÄz+ÃîĞ//Z@üo_:r,o¢ÃrßzoZß*ßªªå~ U¢a>£ÃZUÄ/ä_ZãğäåhüCã+/.obî|ößß,ğ¢ğğ:ÄĞ:*/>+/¢aö.öÄ<ğ:>äO~*~ßÄzå¢<ª£ğı.O,>Ü,åbü@böhıC*<<hbÖä*o©¢h¢Ğüa+A/_@b/ÃBıBªß@ã~zÖZıC@äU_ßUßhvU*a@ÃğÄ:ªZAßAb£U_¢ßbãä:üåãorıÃßª_ãĞÖªzãğåãoaü <ß~zZªaB.+åA¢ãÖ><î:/Ur î¢UßåOaÄ:a|++ª©.r~:/+ä|©ıo++v_@BZ:©©AßCğ.©/Ab<,îß>UãÜÜöbb|ßĞß£:î<<bîöa+,<_aÄ._ª>Ü<|ÖzÃz@>¢ª:a,CÜr__ª.<öÜCã+UÖU¢_üzü bÃ~ßo|, .î,b/U>äıaBZ@Ü£: bÖvıb>Ã/ÜÃ@üÖ/äb¢+r:Zß>ĞÜ|üu©ßZAC:Cßh *.Ã££_ıîu|Urå.:aAUv@u>@<Öü.<ãZ böZAÜÖ£oüĞä*,ü:ğä");
    $stmt = insertTest($conn1, $tableName, true, $inputs);

    $query = "SET IDENTITY_INSERT [$tableName] OFF;";
    $stmt = sqlsrv_query($conn1, $query);
    if (!$stmt) {
        die("Unexpected execution outcome for \'$query\'.");
    }

    // expect this to fail
    $inputs = array("c1_int" => 1264768176, "c2_tinyint" => 111, "c3_smallint" => 23449, "c4_bigint" =>1421472052, "c5_varchar" => "uå©C@bğUOv~,©v,BZÜ*oh>zb_åĞä<@*OOå_Ö<ãuß/oßr <ğãbÜUßÜÃÖÄ~¢~£ bÜ©î.uÜĞ¢ª:|_ĞüÄBĞbüåßÃv@,<CßOäv~:+,CZîvhC/oßUuößa<å>©/Ub,+AĞ©î:ÖrıB+~~ßßßãÜ+_<vO@ ßÃüÖîaCzĞîå@:rı.~vh~r.ÃbÃã©å_îCär BÖÜ:BbUväåöZ+|,CîaAöC,aîbb*UÜßßA hCu¢hOb ğ|ßC.<C<.aBßvuÃÖå,AĞa>ABğöU/O<ÖãüªOãuß£~uÖ+ßÄrbî/:ÖÖo  /_ÃO:uÃzğUvã£Aã_BĞ/>UCr,Äå aÄĞaÃ£vÖZ@ªr*_::~/+.å~ğ©aÄßbz*z<~î©ªrU~O+Z|A<_Büß©¢ö ::.Übıüßr/örh¢:ääU äOA~Aîr<¢äv¢Ä+hC/vßoUª+Oãªã*ğ¢Bö.Zbh/ä,åä>*öğßUßı>aªbBbvßãÖ/bã|ıÖ u.zı©~äğzĞU.UA*a*.¢>î rß ~Cüßaö+rª~ß@aã/ĞCß*a,ªÄbb<o+v.åu<£B<îBZßåu£/_>*~");
    $stmt = insertTest($conn1, $tableName, false, $inputs);

    // expect this to pass
    $query = "SET IDENTITY_INSERT [$tableName] ON; SQL; SET IDENTITY_INSERT [$tableName] OFF;";
    if (AE\isColEncrypted()){
        // When AE is enabled, SQL types must be specified for sqlsrv_query
        $inputs = array("c1_int" => array(-411114769, null, null, SQLSRV_SQLTYPE_INT), 
                        "c2_tinyint" => array(198, null, null, SQLSRV_SQLTYPE_TINYINT),
                        "c3_smallint" => array(1378, null, null, SQLSRV_SQLTYPE_SMALLINT),
                        "c4_bigint" => array(140345831, null, null, SQLSRV_SQLTYPE_BIGINT), 
                        "c5_varchar" => array("Ü@ßaörÃªA*ĞüßA>_hOüv@|h~O<¢+*ÃĞCbazÜaåZ/Öö:ıãuöĞaz£ĞAh+u+rß:| U*¢ªåßÄĞ_vî@@~ChĞö_å*AAıBö¢B,ßbßå.ÃB+u*CAvÜ,ã>ªßCU<åî©ürz¢@ör¢*Öub¢BåaÜ@ª.äBv¢o~ ßıo oîu/>ÜĞÄ,ğ,ğaOÖå>ğC:öZ>ßåğ©<ğ¢+£r.bO.©,uAßr><ov:,ÄßîåÃ+å./||CUÜÜ_ÖÄªh~<ã_å/hbı Ä©uBuß<Ö@boÖıBãCÜA/öÄ:© ßUü*ıvuß.Bãååo_übır_üß>ĞÃÜ£B¢AªvaîvıßCÜUß  åvöuª><îĞUC*aÖU©rªhr+>|äıî|oğröĞ£<ª<Ö|AªohäAî_vu~:~£Ãhü+ÃBuÄğ ü@Z+Ä@hÖî¢|@bU£_ü/£ |:¢zb>@Uß©  Ãão Ö@ãĞBã_öBOBÄĞhCÜb~Ö>îü rıåüUzuãrbzß/ªîUĞğ©uå.ß@£__vBb©/Ür¢Öuåz£ä*å£/*ÃO", null, null, SQLSRV_SQLTYPE_VARCHAR(512)));
        $stmt = insertTest($conn1, $tableName, true, $inputs, $query);
    } else {
        $inputs = array("c1_int" => -411114769, "c2_tinyint" => 198, "c3_smallint" => 1378, "c4_bigint" => 140345831, "c5_varchar" => "Ü@ßaörÃªA*ĞüßA>_hOüv@|h~O<¢+*ÃĞCbazÜaåZ/Öö:ıãuöĞaz£ĞAh+u+rß:| U*¢ªåßÄĞ_vî@@~ChĞö_å*AAıBö¢B,ßbßå.ÃB+u*CAvÜ,ã>ªßCU<åî©ürz¢@ör¢*Öub¢BåaÜ@ª.äBv¢o~ ßıo oîu/>ÜĞÄ,ğ,ğaOÖå>ğC:öZ>ßåğ©<ğ¢+£r.bO.©,uAßr><ov:,ÄßîåÃ+å./||CUÜÜ_ÖÄªh~<ã_å/hbı Ä©uBuß<Ö@boÖıBãCÜA/öÄ:© ßUü*ıvuß.Bãååo_übır_üß>ĞÃÜ£B¢AªvaîvıßCÜUß  åvöuª><îĞUC*aÖU©rªhr+>|äıî|oğröĞ£<ª<Ö|AªohäAî_vu~:~£Ãhü+ÃBuÄğ ü@Z+Ä@hÖî¢|@bU£_ü/£ |:¢zb>@Uß©  Ãão Ö@ãĞBã_öBOBÄĞhCÜb~Ö>îü rıåüUzuãrbzß/ªîUĞğ©uå.ß@£__vBb©/Ür¢Öuåz£ä*å£/*ÃO");
        $stmt = insertTest($conn1, $tableName, true, $inputs, $query);
    }

    $stmt1 = selectFromTable($conn1, $tableName);
    $rowCount = rowCount($stmt1);
    sqlsrv_free_stmt($stmt1);

    if ($rowCount != 2) {
        die("Table $tableName has $rowCount rows instead of 2.");
    }

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);

    endTest($testName);
}

function insertTest($conn, $tableName, $expectedOutcome, $inputs, $query = null)
{
    $stmt = null;
    if (!AE\isColEncrypted()) {
        $insertSql = AE\getInsertSqlComplete($tableName, $inputs);
        if (! is_null($query)) {
            $sql = str_replace("SQL", $insertSql, $query);
        } else {
            $sql = $insertSql;
        }
        $stmt = sqlsrv_query($conn, $sql);
        $actualOutcome = ($stmt !== false);
    } else {
        // must bind parameters
        $insertSql = AE\getInsertSqlPlaceholders($tableName, $inputs);
        $params = array();
        foreach ($inputs as $key => $input) {
            array_push($params, $inputs[$key]);
        }
        if (! is_null($query)) {
            // this contains a batch of sql statements, 
            // with set identity_insert on or off 
            // thus, sqlsrv_query should be called
            $sql = str_replace("SQL", $insertSql, $query);
            $stmt = sqlsrv_query($conn, $sql, $params);
            $actualOutcome = ($stmt !== false);
        } else {
            // just a regular insert, so use sqlsrv_prepare
            $sql = $insertSql;
            $actualOutcome = true;
            $stmt = sqlsrv_prepare($conn, $sql, $params);
            if ($stmt) {
                $result = sqlsrv_execute($stmt);
                $actualOutcome = ($result !== false);
            }
        }
    }
    if ($actualOutcome != $expectedOutcome) {
        die("Unexpected execution outcome for \'$sql\'.");
    }
}

try {
    complexQuery();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Statement - Complex Query" completed successfully.
