--TEST--
Test parameterized insert and fetch sql_variants as strings using various data types
--DESCRIPTION--
The following lists the types of values that can not be stored by using sql_variant:
varchar(max) / nvarchar(max)
varbinary(max)
xml
text / ntext / image
rowversion (timestamp)
sql_variant
geography
hierarchyid
geometry
datetimeoffset
User-defined types
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
﻿<?php
require_once('MsCommon.inc');
require_once('tools.inc');

function createVariantTable($conn, $tableName)
{
    // Do not encrypt the first column because we need to perform 'order by'
    $columns = array(new AE\ColumnMeta('sql_variant', 'c1_int', null, null, true),
                     new AE\ColumnMeta('sql_variant', 'c2_tinyint'),
                     new AE\ColumnMeta('sql_variant', 'c3_smallint'),
                     new AE\ColumnMeta('sql_variant', 'c4_bigint'),
                     new AE\ColumnMeta('sql_variant', 'c5_bit'),
                     new AE\ColumnMeta('sql_variant', 'c6_float'),
                     new AE\ColumnMeta('sql_variant', 'c7_real'),
                     new AE\ColumnMeta('sql_variant', 'c8_decimal'),
                     new AE\ColumnMeta('sql_variant', 'c9_numeric'),
                     new AE\ColumnMeta('sql_variant', 'c10_money'),
                     new AE\ColumnMeta('sql_variant', 'c11_smallmoney'),
                     new AE\ColumnMeta('sql_variant', 'c12_char'),
                     new AE\ColumnMeta('sql_variant', 'c13_varchar'),
                     new AE\ColumnMeta('sql_variant', 'c14_uniqueidentifier'),
                     new AE\ColumnMeta('sql_variant', 'c15_datetime'),
                     new AE\ColumnMeta('sql_variant', 'c16_smalldatetime'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }
}

function insertData($conn, $tableName, $index)
{
    $data = getInputData($index, $tableName);
    $insertSql = "INSERT INTO [$tableName] (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_bit, c6_float, c7_real, c8_decimal, c9_numeric, c10_money, c11_smallmoney, c12_char, c13_varchar, c14_uniqueidentifier, c15_datetime, c16_smalldatetime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    if (AE\isColEncrypted()) {
        $stmt = sqlsrv_prepare($conn, $insertSql, $data);
        if ($stmt) {
            sqlsrv_execute($stmt);
        }
    } else {
        $stmt = sqlsrv_query($conn, $insertSql, $data);
    }
    if (! $stmt) {
        fatalError("Failed to insert row $index.\n");
    }
}

function fetchData($conn, $tableName, $numRows)
{
    $select = "SELECT * FROM $tableName ORDER BY c1_int";
    $stmt = sqlsrv_query($conn, $select);
    $stmt2 = sqlsrv_query($conn, $select);

    $metadata = sqlsrv_field_metadata($stmt);
    $numFields = count($metadata);
    $noActualRows = readData($stmt, $stmt2, $numFields);

    echo "Number of rows fetched: $noActualRows\n";
    if ($noActualRows != $numRows) {
        echo "Number of Actual Rows $noActualRows is unexpected!\n";
    }
}

function readData($stmt, $stmt2, $numFields)
{
    $fetched = 0;
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC)) {
        $size = sizeof($row);
        if ($size != $numFields) {
            fatalError("Array size $size returned different from expected, $numFields\n");
        }

        print("Comparing data in row " . ++$fetched . "\n");

        $obj = sqlsrv_fetch_object($stmt2);
        if (! $obj) {
            fatalError("Failed to retrieve row $fetched!\n");
        }

        $fld = 0;
        doValuesMatched($obj->c1_int, $row[$fld], $fetched, $fld++);
        doValuesMatched($obj->c2_tinyint, $row[$fld], $fetched, $fld++);
        doValuesMatched($obj->c3_smallint, $row[$fld], $fetched, $fld++);
        doValuesMatched($obj->c4_bigint, $row[$fld], $fetched, $fld++);
        doValuesMatched($obj->c5_bit, $row[$fld], $fetched, $fld++);
        doValuesMatched($obj->c6_float, $row[$fld], $fetched, $fld++);
        doValuesMatched($obj->c7_real, $row[$fld], $fetched, $fld++);
        doValuesMatched($obj->c8_decimal, $row[$fld], $fetched, $fld++);
        doValuesMatched($obj->c9_numeric, $row[$fld], $fetched, $fld++);
        doValuesMatched($obj->c10_money, $row[$fld], $fetched, $fld++);
        doValuesMatched($obj->c11_smallmoney, $row[$fld], $fetched, $fld++);
        doValuesMatched($obj->c12_char, $row[$fld], $fetched, $fld++);
        doValuesMatched($obj->c13_varchar, $row[$fld], $fetched, $fld++);
        doValuesMatched($obj->c14_uniqueidentifier, $row[$fld], $fetched, $fld++);
        doValuesMatched($obj->c15_datetime, $row[$fld], $fetched, $fld++);
        doValuesMatched($obj->c16_smalldatetime, $row[$fld], $fetched, $fld++);
    }
    //  returns the number of rows fetched
    return $fetched;
}

function doValuesMatched($value1, $value2, $row, $col)
{
    $matched = false;
    if (is_null($value1) && is_null($value2)) {
        $matched = true;
    } elseif (is_numeric($value1)) {
        $matched = CompareNumericData($value1, $value2);
    } else {
        if (! strcasecmp($value1, $value2)) {
            $matched = true;
        }
    }

    if (! $matched) {
        echo "Values from row $row and column $col do not matched\n";
        echo "One is $value1 but the other is $value2\n";
    }
}

function getInputData($index)
{
    switch ($index) {
        case 1:
            return array(array(1, null, null, null), array(167, null, null, null), array(-28589, null, null, null), array(-1991578776, null, null, null), array(0, null, null, null), array(1, null, null, null), array(0, null, null, null), array(0.0979, null, null, null), array(0.3095, null, null, null), array(0.8224, null, null, null), array(0.6794, null, null, null), array('~<auu*,/Öb£bbör,Aåbßå©+b_ãä¢ä*b<C.Ä/v£*,Buzößý~:ZÜb/Üå£îBðÃ.>Ö~.üoö©UßB.|ÃÄ£*/v|U/*bZ£ÄUÜß*+ööî*©ðü©bðr@Ã©åbOý|©©hob/>Cz<Äå::Ð<¢ß+ü/:ª@zrß.¢Ü£bÜU©ÃßÜßðoß©r*bÜböOUvãahub£ãäªb>_ã£BOÜA©ãü/ß¢ß.ov:Ö<:_+uÜC:£oöü*BzC,Äö~Zî@/Z/r@/©<~.ã¢Aa</*bz.@åýBÄZÃA:zå<~öBbß|ªaýÃ,~><vBîv¢>ü>ý_zz@rÖ¢aU@,ABð/¢ß>z/ã@/ªUA~CoÄ,>bö|Ö>A,v+©CbC/Oo>©ßa©boAîÐvOo>ã|Cåöo+ÃhÖBAbo,+<ßã/£@å+ßAÜ@äÖÜOBäß~öu<aßß_bð¢ýý£_U:*Öä*©Übð,ãß,üððr+ß/U*ã¢ãüß:rAÜåz>*ã+a<îoo|¢üýoBaÃÜ£ãCaC@ha,äzäî¢ü@å£b~råîUbßr©ãßÐ:@UhAO>u*uýBbäZ£aý>v:ðC~ÜöåðzZ>O|Cä+£>öz./Ö+uÜ', null, null, null), array(',ßhr©+|v@,Ã+BZ|îAÐß_öýða_AoäAOÜ*ýC@hoBßßaä+ýöCäAä_Ä¢/Uî.äC©¢rÃuz¢*,ýß.Ðöðý@b£öb.OCý@>hðÖrCZb/Oªz¢A+ªÖäu<ßÜÄ/ÐßÖîbU:bÄÐã>/£ÜÃBÃ@Ð.r:ªª>©zî_ÄÄ:@A.+.aoÖ@¢åOåOBB|+Cvüa_+hz|~COoACAî¢+*Ä©*ýî~|.Äz|u+o~:<@>Arb:~£z<äbãv>Ðr©:ðýCößÖ¢UAîãý:Ã~.C*C¢uÖ*~CÄ*äAb>h@h_>,|u<<r|Ö><.,vå,.BAuo£_ãB.Örö.Ä>zoba~C©hArªB£Zü~oÃbb>î+ääÄCbÐýª*Üýburäßv/åOüA:Oß:obvz©ý/ßroäaª/bªvz©rÐ,Zäß¢ªÄ.ã.@z¢|ð*aCý©:ýÄövã<h+ÜC_ªÄßÜ.@b,Ä,,Ö+ÃüäCvUrÃ_Z,ªî|Üh|bbvýÐðÜoð@bªüb¢öª~åªAB@ðäb/.O@üvUh*z>,öAbö+ÖCb~uÖ£züî|_ö~*CÃ>+ý/_ß+ãÐz<u¢ã@bÖÖßß<r£_Oý+Ã¢,ÖhUv|Ðüð', null, null, null), array('54e16f51-64f1-4d62-a028-582b553c2de5', null, null, null), array('2130-04-16 14:12:00.131', null, null, null), array('2032-05-10 23:32:00', null, null, null));
        case 2:
            return array(array(2, SQLSRV_PARAM_IN, null, null), array(27, SQLSRV_PARAM_IN, null, null), array(-20174, SQLSRV_PARAM_IN, null, null), array(-840346326, SQLSRV_PARAM_IN, null, null), array(0, SQLSRV_PARAM_IN, null, null), array(0, SQLSRV_PARAM_IN, null, null), array(0, SQLSRV_PARAM_IN, null, null), array(0.4880, SQLSRV_PARAM_IN, null, null), array(0.9184, SQLSRV_PARAM_IN, null, null), array(0.6916, SQLSRV_PARAM_IN, null, null), array(0.7257, SQLSRV_PARAM_IN, null, null), array('<ö©ååä,ääÐ*bhîvr<Cý¢î©ßZÐ_å©ÐZ,UC:<öa</©bU*Uö@u*AðaßÃBÄßh+o+üÐC~*böÃ:*OaÄ*|£z|+rZ_¢ßv~~aÖ©ß£ZbÃ+CÜîä~|îÄÐaB+_b>~aöb/BzZÜ@öðß@_Ä££r__£>£Ð£ðbU<r<Äou£+ývUß<h+£¢BOrz<Ä*@öö@z*ÜoubZZ<u¢Zå+£,öÐb@haB_Öåöa@hOuZhA©>B~/ãbo.>îzöã*,ßå/+zuu.+BZßzA,aÖzüåão£©BãÄbä~ýooÜ,+äßÐ:UÃrz|vä,Bå~¢ä<_£uÜv<_O|ßBC¢_£Ahöª_¢oözCßýzöüý+zÄUÖhB@Uîbh/u/©zÐbÖ¢A*ã,Ãî£<>rUªßÐßîZîåb:+¢|A_BÃo©ªäu,*ýååbU:bÖÄß|¢>¢ÖaãrÃO©Äv+oßöZãª,+/.ãa/ã£ª,¢ðÐ<î¢b.£Ü©_r©vª@î:>ÖðB:OrBÜÐªý|bßbÜ|åUOåîOãÄãuÐ|/îörB£ÃßZZÄ@Z©bÜB:.¢@b££U¢äÐvÐ+ý+uzÃb+üo+öv~_©~Uhbª,ßCb+UZö>Üü', SQLSRV_PARAM_IN, null, null), array('|öÐob*+ÐÖ<UU|ßbCªb¢ªuCüoUOü:Ü/>,..Ä¢ß@>îß*äî|å>~Oo+/o+*/ü|îî,ðö*ýåãob:zb|Äßîvb¢,Ã,UªbbrAbZ©uªª@ä,_ð©A*>Ðävä:|:oîö_rý©+vî©ßBßßb>üOö@Öoö*+î@ÐßrÖ<¢hÜZb._raUaýUUÄößßîU¢ð.ÐýrãBh¢>Äðz<©AÜ/|©Ö@>hüBCO~öýZ>äÄÐAzä~/b.ÜzbðÜbða++ªå/ð~ACÐî~©>./<Ööý<~ýuÃBÐãåo*h©ö£öîüZß:ZÐä_>Ðvî©<aBð_ß++OzOÖhö,ÜîÐä<_><h|OãhÄr+<öuÄ*AÐ.ä~_Üý¢Ähß<Ö~a.:Cü|ü++öu©ýöÐßîUbÐOzaåýÄ>_äbb©ö¢*b@BÐÜb+bî+åßAåîu|/A.Ä.~hvb:@zå|Ä,ªÃZß@v©ßvB@Bð:£öß@uðr££ðü<Ä¢äÖaßO.:rª/Ao,ª:ZbA+¢ß|>,*ßoöA+ãb|Aü@bÄð@a:+,<b_¢r*åöBbßZU£<.U>ouªýª+£ðr*Bã¢+rCðUU_ÖÃ>îö>r©v:U_v@vCÜ>', SQLSRV_PARAM_IN, null, null), array('29a27f4f-9e94-45a9-9110-812ef69ee37c', SQLSRV_PARAM_IN, null, null), array('4262-03-20 19:16:36.081', SQLSRV_PARAM_IN, null, null), array('2065-02-17 00:36:00', SQLSRV_PARAM_IN, null, null));
        case 3:
            return array(array(3, null, null, null), array(170, null, null, null), array(25360, null, null, null), array(1352271629, null, null, null), array(0, null, null, null), array(0, null, null, null), array(0, null, null, null), array(0.3807, null, null, null), array(0.4393, null, null, null), array(0.8725, null, null, null), array(0.2057, null, null, null), array('ZÄßÃ|vbB/O<ouU*+ð>ýÖ~AABß©Ã@ÄÖßz~åz@ü.Ö<*~<B/Ob_ðð<öå<vUÃÃîîª|.Ã,oª+öã._Ö_.a~ðÃªªhªÜvã/Cªbßv>ãäßOÜÄv~Äªb_ör*bvÃÖýZZ<ö¢.|Ð>ÜåaCAîÃ¢ãßu/aå|@U*¢Bb*+bZr_.ã|,h_BöÄb.ðZ©//î_~v/ð/,bð¢/:@öãß+vÜv/båðöã:ã/z:£î<_ÐöC>.Ozrð©@rC~Bö,£o<:Ã*<bý_©¢Oß_b.Cåuß+BCäüv/£zvz,býÐýbAª£./£©ªÐäüoÐ:uÄªvb+ªÄbb:©U*uhßßb*bZª.ð:<UU/<<ªßBÃ<ÄÃ¢>z_ªöÜ,z,ªboB,+öCr*¢î*<£~ýb:U|©Bh/ãÜÖý:obhå£+Z+r:o|v+bÐhãåaüÐöbãðöAÃ|ªOCÖO|Ü<ãvv¢ãýbý.ÐbÄÃðåü>/BbbÄ/vÃ©äý:@o>öÃaªÐ+îüýã_röýä©zhvÜ<Ã/CäaðoCB|å~~ÖaðvuC_hBrOrzÃßO©ZU.AvvåÖÐ/ÐãåZ©£,UãÖAîhUzªrö£Ãu+ð/v¢o_<ÐA@', null, null, null), array('ÄßZrð@~ö:ü:£,CoÄ©böBAO,ð:aA>ãÜBÐ@./:A.Z/bÖÜ,>ßî>ýßß©b/<@/,Öî>BBÃäÐCüÃÐÃvÜ_AZ.ý/©C_>aö/£Böða©£,öý£B_ÜÃðßvh|î|.oB/öBÜö¢BÐ/bAAÜÄa£.ªA©z<£ýOÐrå._bÄÜß~Ä_ªý,|+BÃ£îA~Cî@ü+@ÜüzCªr.rzåazUöCzBßª©Bö+ü*ZÃ£Ö@AC*UA¢..aÜü*ArÃz£B:ßßÄ+Ã/ãª+ßZ_Ü<ßäîýýî@ðÄÜßÃÖðova£ªOöÄzÖ©ãrabªÐUrår+Ü*©OöåBö|a©î:bß©ð~_C_o*hÃ@åBb|<åÄß@©ý.Ubª,O£Oz|üßbz£+bã¢a@>:aaîý_Ür£|hÃ@z<_hüÃü,öîZýuã_¢üå£<ðßAª>rC.Bî.©,ß*å|Ã©*_B>CÄîÖÃU~ÃÃ>rª>/ð©Ö|~ZA>¢¢/@£bZuZößzðå~:/h@uÐoOrã<¢aîßüß<¢BZzO¢@.:rvÜo>ABzC/ÜßÖ::r©O/v*@üaäzßZhU@aßvüî:©ü~ðª©_b£ä£ãB@:bhCÄZÜzOUßoåîÜý><', null, null, null), array('55a1f242-dad9-4f8e-b839-364fb6e1ffec', null, null, null), array('7060-11-11 17:57:33.899', null, null, null), array('1920-07-05 00:42:00', null, null, null));
        case 4:
            return array(array(4, SQLSRV_PARAM_IN, null, null), array(229, SQLSRV_PARAM_IN, null, null), array(-13459, SQLSRV_PARAM_IN, null, null), array(-8557402, SQLSRV_PARAM_IN, null, null), array(0, SQLSRV_PARAM_IN, null, null), array(0, SQLSRV_PARAM_IN, null, null), array(1, SQLSRV_PARAM_IN, null, null), array(0.3122, SQLSRV_PARAM_IN, null, null), array(0.3036, SQLSRV_PARAM_IN, null, null), array(0.8606, SQLSRV_PARAM_IN, null, null), array(0.4224, SQLSRV_PARAM_IN, null, null), array('uåîAZ©ÄZöäÜO@ÐðZ:r:vÜ@ýA/,O,ãBß>¢hð.Z>£Bßª+¢ªZU/@@äB.orÐîå¢ð>*<äAv~,ß@ýü~+<oo+/hCÜåzuÐb:ðr@.åÄ¢<h>~*Ðå¢ý_å_bÄb~_<<ßÄ:o¢zrC<ªa~BäÐýA©ßB.Uhîß+rAÖå¢ýö.îîaýåUC¢/Aüöß©zÄaðÐ,b|||+vCO+~üA£ÄöãýbÜ_üßãCðã|_ÄbäÃU~***<¢¢höUãbözbÄ>Ðühr.vÐ:£_Ä~<ZÐvÜb+¢>@/o,a_abý_>ßr*å|bob¢îãBî~<üBÜouÄBar_üß.ÐÐ,©ýuÖUöäÐÐîZýªÃ*vß|~îZßZÜÐrh*~UÜ*î@OBÃßraUb:*/B/@OÄaãoßBãÃ<AAß@o>höBb@Uªý*|£U+ü*¢ß¢häUðOb/*.rßOrÖåüO<ý*aöCa@ªoä>ÐC.UO+ZUrÜA©Oã,ro+î+,Üå¢<¢rÐu.ªî|ãÃB,ða,ªÐüÄü©ßBÖß+uv>BöußÜ|h|aßohB*ovãu+@Ü£ßO©ßßBý:b+£bÐÖäªo:UAÐo_ã~>ö<ühåÖÐ<hbCýÜA~|Üß', SQLSRV_PARAM_IN, null, null), array('Aª./UbîvÐð££ãuzo@>î>å*v¢ß<OÃr<o.Ð*_å|bÄbü>a_>/ZÖ:åbbäz¢Ä*Ü¢ÜåubvÖUî@Ã:¢<Üß_.*Öh,o/uz.B_/Äã|Ü/öOÐ.ÐßU@ßbav~zAßu+ª£U¢<ýÖä©Ä>ßãåäbã¢ßýªöåä,*ubßß@¢><üCozÐÐ©äC_aauC/_<.ýuÜ£Ö,uCÜÃbåräZ,ðÐî@îbzÖã+ã,CB£ZzB¢vÄ*+Üb¢üýßU*oÄärÃ£ü@öîaß.|äý©bÖ|BuA©ª,C/ZB*ð~aÃÃvîü©+ªÃ+Ã_öuu¢ZöbÄuð©O¢Z@_uä|bu,äOÐÜbBr@|Ãüb/îr©ß.ååßÜabZ©hß+ãßªz|+Aå@äü>Ä+ýu|å¢z|bhr*ªbO©>/ö,hÐå+Öå_OßZ|ð,b.AäÐß_@ßß©.üüZäuA/aC|£CäßýbhÖÖªZö@ÃhßÖ£/å*örüßðU*~vhðv_Üðýðß<+bAbBa:ªÄ/vÜªUuÄîîabãßO>:,Ãýðußßäö@vîhäÜ>£¢hý+zAZbaBðÐ£|å|ªÐ*:Ã>ª:ð£ÐüßÖbuªOOA>Bb©ÃärÐîhzö,+|C:Aö', SQLSRV_PARAM_IN, null, null), array('34eeff6c-7d28-4323-9e28-d6b499fde336', SQLSRV_PARAM_IN, null, null), array('4191-02-05 02:41:51.953', SQLSRV_PARAM_IN, null, null), array('1975-12-01 15:24:00', SQLSRV_PARAM_IN, null, null));
        default:
            return array();
    }
}

try {
    setup();
    $conn = AE\connect();

    // Create a temp table that will be automatically dropped once the connection is closed
    $tableName = 'param_input_variants';
    createVariantTable($conn, $tableName);

    // Insert data
    $numRows = 4;
    for ($i = 1; $i <= $numRows; $i++) {
        insertData($conn, $tableName, $i);
    }

    fetchData($conn, $tableName, $numRows);
    
    dropTable($conn, $tableName);
    
    sqlsrv_close($conn);
} catch (Exception $e) {
    echo $e->getMessage();
}
echo "\nDone\n";

?>
--EXPECT--
﻿Comparing data in row 1
Comparing data in row 2
Comparing data in row 3
Comparing data in row 4
Number of rows fetched: 4

Done
