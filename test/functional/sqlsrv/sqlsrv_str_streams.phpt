--TEST--
reading different encodings in strings and streams.
--SKIPIF--
<?php require('skipif_unix.inc'); ?>
--FILE--
<?php

function setup_test($conn, $field_type)
{
    prepare_params($params, $field_type);
    $tableName = "[dbo.TestTable" . $field_type . "]";

    put_image_into_table($conn, $params);

    return $params;
}

function start_test()
{
    require_once('MsCommon.inc');

    sqlsrv_configure('WarningsReturnAsErrors', 0);
    $conn = connect();
    if (!$conn) {
        fatalError("Failed to connect.");
    }

    $params = setup_test($conn, "varbinary(max)");

    echo "retrieving binary encoded varbinary(max)\n";

    $stmt = sqlsrv_query($conn, $params['selectQuery']);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $result = sqlsrv_fetch($stmt);
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $db_stream = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
    if ($db_stream === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $file_stream = fopen($params['testImageURL'], "rb");

    while (($file_line = fread($file_stream, 80)) &&
            ($db_line = fread($db_stream, 80))) {
        if ($file_line != $db_line) {
            die("Binary not identical");
        }
    }

    sqlsrv_free_stmt($stmt);
    fclose($file_stream);

    echo "retrieving char encoded varbinary(max)\n";

    $stmt = sqlsrv_query($conn, $params['selectQuery']);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $result = sqlsrv_fetch($stmt);
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $db_stream = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR));
    if ($db_stream === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    while ($db_line = fread($db_stream, 80)) {
        echo "$db_line\n";
    }
    sqlsrv_free_stmt($stmt);

    $params = setup_test($conn, "varchar(max)");

    $stmt = sqlsrv_query($conn, $params['selectQuery']);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $result = sqlsrv_fetch($stmt);
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    echo "retrieving binary encoded varchar(max)\n";

    $db_stream = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
    if ($db_stream === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $file_stream = fopen($params['testImageURL'], "rb");
    while (($file_line = fread($file_stream, 80)) &&
            ($db_line = fread($db_stream, 80))) {
        if ($file_line != $db_line) {
            die("Binary not identical");
        }
    }

    sqlsrv_free_stmt($stmt);

    echo "retrieving char encoded varchar(max)\n";

    $stmt = sqlsrv_query($conn, $params['selectQuery']);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $result = sqlsrv_fetch($stmt);
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $db_stream = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR));
    if ($db_stream === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $file_stream = fopen($params['testImageURL'], "rb");
    while (($file_line = fread($file_stream, 80)) &&
            ($db_line = fread($db_stream, 80))) {
        if ($file_line != $db_line) {
            // continue testing even if the data not identical
            echo("Characters not identical!!\n");
            break;
        }
    }

    sqlsrv_free_stmt($stmt);

    $params = setup_test($conn, "nvarchar(max)");

    $stmt = sqlsrv_query($conn, $params['selectQuery']);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $result = sqlsrv_fetch($stmt);
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    echo "retrieving binary encoded nvarchar(max)\n";

    $db_stream = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
    if ($db_stream === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $file_stream = fopen($params['testImageURL'], "rb");
    while (($file_line = fread($file_stream, 80)) &&
            ($db_line = fread($db_stream, 80))) {
        if ($file_line != $db_line) {
            die("Binary not identical");
        }
    }

    sqlsrv_free_stmt($stmt);

    echo "retrieving char encoded nvarchar(max)\n";

    $stmt = sqlsrv_query($conn, $params['selectQuery']);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $result = sqlsrv_fetch($stmt);
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $db_stream = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR));
    if ($db_stream === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    while ($db_line = fread($db_stream, 80)) {
        echo "$db_line\n";
    }
    sqlsrv_free_stmt($stmt);

    sqlsrv_close($conn);

    echo "Test successful.\n";
}

function put_image_into_table($conn, $params)
{
    drop_test_table($conn, $params);
    create_test_table($conn, $params);

    $data = fopen($params['testImageURL'], "rb");
    if (!$data) {
        die("Couldn't open image for reading.");
    }

    $stmt = sqlsrv_query($conn, $params['insertQuery'], array( array($data, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY))));
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    do {
        $read = sqlsrv_send_stream_data($stmt);
        if ($read === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    } while ($read);

    fclose($data);
    sqlsrv_free_stmt($stmt);
}

function prepare_params(&$arr, $field_type)
{
    $uname = php_uname();
    $phpgif = "\\php.gif";

    if (isWindows()) {
        $phpgif = '\\php.gif';
    } else { // other than Windows
        $phpgif = '/php.gif';
    }

    $arr['tableName'] = $tblName = "[encoding_test_" . $field_type . "]";
    $arr['columnName'] = $colName = "php_gif";
    $arr['fieldType'] = $field_type;
    $arr['dropQuery'] = "IF OBJECT_ID(N'$tblName', N'U') IS NOT NULL DROP TABLE $tblName";
    $arr['createQuery'] = "CREATE TABLE $tblName ($colName $field_type)";
    $arr['insertQuery'] = "INSERT INTO $tblName ($colName) VALUES (?)";
    $arr['selectQuery'] = "SELECT TOP 1 $colName FROM $tblName";
    $arr['testImageURL'] = dirname($_SERVER['PHP_SELF']) . $phpgif; // use this when no http access
    $arr['MIMEType'] = "image/gif";
}

function drop_test_table($conn, $params)
{
    run_query($conn, $params['dropQuery']);
}

function create_test_table($conn, $params)
{
    run_query($conn, $params['createQuery']);
}

function run_query($conn, $query)
{
    $qStmt = sqlsrv_query($conn, $query);

    if (!$qStmt) {
        die(print_r(sqlsrv_errors(), true));
    }

    sqlsrv_free_stmt($qStmt);
}

start_test();

?>
--EXPECT--
retrieving binary encoded varbinary(max)
retrieving char encoded varbinary(max)
47494638396178004300E66A007F82B839374728252ACCCDE2A1A4CBD3D5E7B2B4D44342588386B9
8283B35252729092C2C2C4DEAAACD04C4B635B5C83DDDEEC3B383C6E71A56A6D9D61638D7579B17B
7EB5E5E6F0999CC68C8DC1B9BAD96B6B924E4E6B7174A97A7AA3888BBD7274A37473988E90C15A5B
7EE2E3EF7B7DADA4A5D06D70A27276AC9596C8BBBDD97478AE8588BB9295C3D8D9EA9292C4646692
6B6E9FA5A8CE9496C52E2B2F535168B3B4D76C6A8C5C5B768A8DBF666896686A9A9C9FC8312E39AE
B0D39C9CCD5556789EA1CA9699C58182AF6769973F3D50BCBEDA5E60899899C88C8EBF898ABA5758
7CB6B7D7D5D7E8221E206C6F9ECED0E4BFC0DC777BB47678A75F5E7D9999CC6E6F987377AE221E1F
FFFFFF908E8F595657C7C6C7EEEEF5D5D4D5F6F6FA828081ACAAAB747273F1F0F19E9C9DE3E2E34B
4849B9B8B96764658989B600FF0047465FDFE0ED9B9CCC696B9C6F73A78C8CBAB8B9D9B8BAD79A9B
CCA7AACFDBDCEB9A9CC9666484686789A7A8D26E70A08B8BB9C6C7E0C7C9E1696C9C9798CB21F904
0100006A002C00000000780043000007FF806A82838485868788898A8B8C8D8E8F90919293949596
97966D3F6D73989E9FA03F7979363E1A714C1A1A4C063E3E0D3204413C18422D0B4922391F193922
2F29A0C2A10D1A0C7D4D055050037D7C0C51512A72ACAF74B1B342332DBA1F080016161557282516
0919C1C3EB8C26717C2E6C6C1010752EC903D432B3221FFE2C0011080440B0E03782E1A4AC40F146
C2093F3B9E4C1992849D4535416C0C6043E282471224F23510B2408AC9932853AA5C79725C070913
76E88041E108052B43445CC4148409148E1E2FB83042A705CBA34893A2BCF226068C23471E8C5872
C483CE9D907E687401812304230458281D4B36E995133A1E2C01A240C1882933FFB02E32A1A14013
17750A184852B6AF5FA41D602C51C08183033C09E41ACA1365009402037CE4F8BB720186CB98317C
A09CF40D05050ED6AC51E041B19A3C46F8F419C06406E7951FB270994D9B4B97D74ADD2C5973E080
83103BDD4561C0A00102DC2B3170C1C2BCB999DBC89376D85DA4C89AD2C37EF8906344050F0BD157
AA20D3BCB99802E1955E79702040000E70409930C0440E0100E9571610539E391915F929F58602EE
F570072673D0E18301321C17A04A5D98D11F165C10F0A05231ACD1430F07EC51C90F3234D0404917
A6044016136291C5662526F540001B0227491B04ECE3608B272937A1195FE0A8941F45D040031591
D8110401338087D4FF079935D9248B47A910C684625CD024892C31E9E496F88D858203340850C323
4860100494477DF045166CB6E9E69B59745140035DA2540018139201679B170C60E1490BEC29289B
5D0CD0805240082080038DCC200406622945C07229566A06185C7C01204A5D6C51698A346CA14519
5D9068C0949F4EB805185E747128522308E004A38A6C23C48D498D97EAA75B7801419727EEFA2918
636020C500780AAB6A1903243582134EE090480A22B480665210F0A76C8A61406012065E6C9B221A
B75DE0A9B8E5D1E045B348AD012D7685FCE1CB64657D2121BAFD7901A094F8E6DB008AFD36474319
7F1E158013022C60483FF492155BC0E56DD1E39D1033A7C5FF05E1568C8518D01DD581AC63129202
021FD439D6A43BC239C619DA4EE805019D4EC8C59B637081468A5A64816AC4709671C6B913968125
4B0A409B0621DEE03A96AEFD89E12D4A0B34D00579136AC100C0FDAD8812060D7C1141D559685135
BB801AF045B2FD8561805234CC3AC81F0020A06459D9E6B9A989597CDD74161997C7E34A1000DD5C
CE823377C6DA2A7D3006CE771FB504B40AAB218205269365AFCC05A714B8AA3A53F9744A31F77746
1634046DEC4AA197A745E32C7500ED0D82B090505F0F4F38C6B5276DDE1F1A59A0DDDC7F2A051B74
1913D290C551A90FCE3A4B45B8AD4638739385727F11F4C8D2E5FD118EB94AE0169FC519AA5E7014
FFD6E501AF14074EF420480594315D1E1A9FA7547BDAE43777BB4AFCF6B745D863B38401F1DB530A
05A025881550A66ED95B9E490C00BEA0F5AD39D5D38FEF9803862C140E0B87630903A8D69FFB0990
8029B802FBFC82BDF2548825C9634E043AB7BBF89D2485580803E94CB71200786D42E422CB009D20
081418B02FC2EB20EE8E45A9ECF5AE6ACB0B6279BC00C0741D4F3F1C2C4F1814B8921D0AA2032818
21597444BD27A2E40305F042E9FA4383317CE182274C49F726F4BDF0710F02629C50F52A77142BAA
61215A5C5A1499432E15F8510510E8C218B43046D10DA07ECCF1204AF2C733B165AF0B7F046417CA
E04899517125E853DFF33A7085BEEC47FF555A086528C570AF1489E10B0478A00ABD68A70962A182
17149528B540CA6189AF2CCDA3D507DED081BE44486368F8C2024E8543179AC45C139261210376CA
A149E77582488104DEF043A528B15FCD3C962BADB612446281891A038330FBF2382744EE7912E8E5
58D6D82F1A9C619C5240667FB8F0AAADA992396D0C5804CE700167B2CD798278C10924D049A53052
5C6620C3180650A71976D09FC4D41FFFF065061932808E49299A138E4688219CE00D63A198FE6639
4B3270610C854253A0481A4A569E44055C60E9E82E28069686D2A42865C010A5033243A4E0042750
275260C8850BD0831E05F82301FC6912401EF5A89903D4009E4A8F432E93395EFF30EA5193EA4702
EC742C074BD82192108313A00029D74C24537D24850534B13C59C02872DCE50478192201652D6872
EEB942B6B2A4010D8C58C7F2534E6929A2047965C941F9684CBF4A8101956C0E18D013A072D26A11
8835AB04ABC600C7AA447752BC2465347A594694600231E8401EA540D47A7AD6247973D9E9A2D381
0340ABB48D48006A25A0C5B462610C6BF5D10216C746B9FA85083D80966123A184274C40B352F8DF
16A64B5DDEBDD69ED4A52E1806FB1A05C84A00329AC40B40E08718F0160005A02A3D10775D29A057
BD10702D65887030274480A39648801B50FB86D5B6D7AF27B0AD7241F18229F801B5AAFDAF5F51C0
015939A108F80585FF12F4E08603F757C1253AC11A1CDC83F0B28305143EB004CE8AE1F01CE1008A
12400F60A718168060076E70430CA85962CAF881030108930002C062D30822092520C20E603C011A
D738291320500F842426BBFA78102948801E74400421BB21A8FEC5F01B8E80E30D2DB9087878C193
17F10229EBE0CC67DEC147497CDD1D1C4101ED81D1860E70030F8DF9112F40C0148800833EFB9908
7EF8A850F33301223C0034D5718FA27110023BDF99122218C29E2940E94A53FA087DAE70A0DFC069
4E4BE0D39F062A50897069B688A63707A84E110270001C6C20318F16460A3E50021058012A507980
AE773D9525AC05086C690B610A6398D09CBA37455000156E38300431C75A31295002023CE0810D6C
202DBE0676B0DB426CC370800A23188115AC5082663FFBDC8B10411A10701574BBFBDDF08E77BC03
01003B30
retrieving binary encoded varchar(max)
retrieving char encoded varchar(max)
retrieving binary encoded nvarchar(max)
retrieving char encoded nvarchar(max)
???xC???????????????????????????????????????????????????????????????????????????
????????????????????????????????????????????„???????????????????????????????????
?????¶ÿ???????????????????????????????????  xC?????????????????????????????????
????????????????????????????????,???????????????????????????????????????????????
????????????I???????????????????e???????????????????????????????????????????????
???????????D????????????????????????????????-?@??????????????A??????????????????
???????????T?ƒ?????????????????%?z??????????????????????????????????????????????
?????????????Z??Û??????????????????????????????a????????????????????????????????
???????? ????????????????????????????????????u?????????????????????????????????
?g??????????????????????????????????????????¦??????¨?????Ë???????????¼?????????
????????????????????????????????????????????????????????????????????????????????
????????????????????????????????U???????????????????????????????????????????????
???????????Ô?????????????????????????G??????????????????????????????????????????
???????????????????????????????????????????????????????????????????????????????
??????????a?????????????????????????????????????L???????????????<???????????????
??????????p??????R????????????P????????????????????????????µ?
Test successful.