--TEST--
Test fetching various data of type sql_variant by binding columns and other fetch styles
--DESCRIPTION--
The following lists the types of values that can not be stored by using sql_variant:
varchar(max) / nvarchar(max)
varbinary(max)
xml
text / ntext / image
rowversion (timestamp)
sql_variant
geography
hierarchyid
geometry
datetimeoffset
User-defined types
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function createTestTable($conn, $tableName)
{
    try {
        $colArr = array("c1_int" => "sql_variant",
                      "c2_tinyint" => "sql_variant",
                      "c3_smallint" => "sql_variant",
                      "c4_bigint" => "sql_variant",
                      "c5_bit" => "sql_variant",
                      "c6_float" => "sql_variant",
                      "c7_real" => "sql_variant",
                      "c8_decimal" => "sql_variant",
                      "c9_numeric" => "sql_variant",
                      "c10_money" => "sql_variant",
                      "c11_smallmoney" => "sql_variant",
                      "c12_char" => "sql_variant",
                      "c13_varchar" => "sql_variant",
                      "c14_nchar" => "sql_variant",
                      "c15_nvarchar" => "sql_variant",
                      "c16_binary" => "sql_variant",
                      "c17_varbinary" => "sql_variant",
                      "c18_uniqueidentifier" => "sql_variant",
                      "c19_datetime" => "sql_variant",
                      "c20_smalldatetime" => "sql_variant",
                      "c21_time" => "sql_variant",
                      "c22_date" => "sql_variant",
                      "c23_datetime2" => "sql_variant");
        createTable($conn, $tableName, $colArr);
    } catch (Exception $e) {
        echo "Failed to create a test table\n";
        echo $e->getMessage();
    }
}

function insertData($conn, $tableName, $numRows)
{
    try {
        for ($i = 1; $i <= $numRows; $i++) {
            $stmt = $conn->query(getQuery($tableName, $i));
        }
    } catch (Exception $e) {
        echo "Failed to populate the test table\n";
        echo $e->getMessage();
    }
}

function fetchBoundMixed($conn, $tableName, $numRows)
{
    $query = "SELECT * FROM $tableName ORDER BY c1_int";

    $stmt = $conn->query($query);
    $numCols = $stmt->columnCount();

    $cols = array_fill(0, 23, null);
    $stmt->bindColumn('c1_int', $cols[0]);
    $stmt->bindColumn('c2_tinyint', $cols[1]);
    $stmt->bindColumn(3, $cols[2]);
    $stmt->bindColumn(4, $cols[3]);
    $stmt->bindColumn(5, $cols[4]);
    $stmt->bindColumn(6, $cols[5]);
    $stmt->bindColumn(7, $cols[6]);
    $stmt->bindColumn(8, $cols[7]);
    $stmt->bindColumn('c9_numeric', $cols[8]);
    $stmt->bindColumn('c10_money', $cols[9]);
    $stmt->bindColumn('c11_smallmoney', $cols[10]);
    $stmt->bindColumn('c12_char', $cols[11]);
    $stmt->bindColumn(13, $cols[12]);
    $stmt->bindColumn(14, $cols[13]);
    $stmt->bindColumn('c15_nvarchar', $cols[14]);
    $stmt->bindColumn('c16_binary', $cols[15]);
    $stmt->bindColumn(17, $cols[16]);
    $stmt->bindColumn('c18_uniqueidentifier', $cols[17]);
    $stmt->bindColumn(19, $cols[18]);
    $stmt->bindColumn(20, $cols[19]);
    $stmt->bindColumn(21, $cols[20]);
    $stmt->bindColumn(22, $cols[21]);
    $stmt->bindColumn('c23_datetime2', $cols[22]);

    $stmt2 = $conn->query($query);

    // compare data values
    $row = 1;
    while ($result = $stmt->fetch(PDO::FETCH_BOUND)) {
        echo "Comparing data in row $row\n";

        $obj = $stmt2->fetch(PDO::FETCH_LAZY);
        if (! $obj) {
            echo "Failed to fetch data as object\n";
        }

        $j = 0;
        foreach ($cols as $value1) {
            $col = $j+1;
            $value2 = getValueFromObject($obj, $col);
            doValuesMatched($value1, $value2, $row, $col);

            $j++;
        }

        $row++;
    }

    $noActualRows = $row - 1;
    if ($noActualRows != $numRows) {
        echo "Number of Actual Rows $noActualRows is unexpected!\n";
    }

    unset($stmt);
    unset($stmt2);

    return $numCols;
}

function getValueFromObject($obj, $col)
{
    switch ($col) {
        case 1: return $obj->c1_int;
        case 2: return $obj->c2_tinyint;
        case 3: return $obj->c3_smallint;
        case 4: return $obj->c4_bigint;
        case 5: return $obj->c5_bit;
        case 6: return $obj->c6_float;
        case 7: return $obj->c7_real;
        case 8: return $obj->c8_decimal;
        case 9: return $obj->c9_numeric;
        case 10: return $obj->c10_money;
        case 11: return $obj->c11_smallmoney;
        case 12: return $obj->c12_char;
        case 13: return $obj->c13_varchar;
        case 14: return $obj->c14_nchar;
        case 15: return $obj->c15_nvarchar;
        case 16: return $obj->c16_binary;
        case 17: return $obj->c17_varbinary;
        case 18: return $obj->c18_uniqueidentifier;
        case 19: return $obj->c19_datetime;
        case 20: return $obj->c20_smalldatetime;
        case 21: return $obj->c21_time;
        case 22: return $obj->c22_date;
        case 23: return $obj->c23_datetime2;
        default: return null;
    }
}

function doValuesMatched($value1, $value2, $row, $col)
{
    $matched = ($value1 === $value2);
    if (! $matched) {
        echo "Values from row $row and column $col do not matched\n";
        echo "One is $value1 but the other is $value2\n";
    }
}

function fetchColumns($conn, $tableName, $numRows, $numCols)
{
    try {
        // insert column data from a row of the original table
        $stmtOriginal = $conn->prepare("SELECT * FROM $tableName WHERE c1_int = :row");

        for ($i = 1; $i <= $numRows; $i++) {
            $c1_int = $i;

            echo "Insert all columns from row $c1_int into one column of type sql_variant\n";
            $stmtOriginal->bindValue(':row', $c1_int, PDO::PARAM_INT);

            // create another temporary test table
            $name = 'row' . $c1_int;
            $tmpTable = getTableName($name);
            createTable($conn, $tmpTable, array(new ColumnMeta("int", "id", "identity(1, 1)"), "value" => "sql_variant"));

            // change $c1_int now should not affect the results
            $c1_int = 'DummyValue';

            $stmtTmp = $conn->prepare("INSERT INTO $tmpTable ([value]) VALUES (?)");
            for ($j = 0; $j < $numCols; $j++) {
                $stmtOriginal->execute();
                $value = $stmtOriginal->fetchColumn($j);

                // insert this value into the only column in the new table
                $stmtTmp->bindParam(1, $value, PDO::PARAM_STR);
                $res = $stmtTmp->execute();

                if (! $res) {
                    echo "Failed to insert data from column ". $j +1 ."\n";
                }
            }

            // now select them all and compare
            $stmtTmp = $conn->query("SELECT value FROM $tmpTable ORDER BY [id]");
            $metadata = $stmtTmp->getColumnMeta(0);
            var_dump($metadata['sqlsrv:decl_type']);

            $results = $stmtTmp->fetchAll(PDO::FETCH_COLUMN);

            $stmtOriginal->execute();
            $arrays = $stmtOriginal->fetchAll(PDO::FETCH_ASSOC);
            $columns = $arrays[0];  // only the first set is needed

            $j = 0;
            foreach ($columns as $column) {
                if ($j == 0) {
                    $val = sprintf('%d', $i);
                    doValuesMatched($results[$j], $val, $i, $j+1);
                } else {
                    doValuesMatched($results[$j], $column, $i, $j+1);
                }
                $j++;
            }

            unset($stmtTmp);
        }
    } catch (Exception $e) {
        echo "Failed in creating a table with a single column of sql_variant\n";
        echo $e->getMessage();
    }

    unset($stmtOriginal);
}

function getQuery($tableName, $index)
{
    $query = "";
    switch ($index) {
        case 1:
            $query = "INSERT INTO $tableName ([c1_int], [c2_tinyint], [c3_smallint], [c4_bigint], [c5_bit], [c6_float], [c7_real], [c8_decimal], [c9_numeric], [c10_money], [c11_smallmoney], [c12_char], [c13_varchar], [c14_nchar], [c15_nvarchar], [c16_binary], [c17_varbinary], [c18_uniqueidentifier], [c19_datetime], [c20_smalldatetime], [c21_time], [c22_date], [c23_datetime2]) VALUES ((1), (110), (-28270), (804279686), (0), (0), (null), (-100000000000000000000000), (0.6685), (0.2997), (0.5352), ('äðubý/ö*bUah¢AoÖrZÃ_oßoüöÐ>ßÄßUAüîÖh_u*uh.uå:,öî@<BCãå,AÖvBvöC¢ZaüoÐZarüö<.Ö~Z@~Ü~zUÄCrB_Ä,vhbZaÜöä<ruª>UCO,<¢<:Ö@>+ß,ªåÜbrª¢öãäo,ü£/b,|ýãý~öß£îUö_¢ªðu.+ýÃhAaäzvzrb£ßAÃhö,ö.aöü/Z+Ã.uvUo~v:+u_ýý©z¢ª|U/îã<©|vý+bÐÄÐ©oðbbüðb_~*î..üÐÃz,äAðß~Ö¢Äå~ð.£_ßzãÖv~¢£Oå*@|UozU©Ð+ãÄÐ,*Z/vA>ªOÄ,¢bhý/ÖÖuäA<bO+||zv©vÃBª<.ýh+¢ÃvhßO£bOvUýª¢äÄðvBbÄ<O*@/Ä@<©~:ª,¢oÖzUaÐ<,baÃÃbuå_CåB£h@ö£.<Cª@Ãß.raÃöªAb*UBCzãÐ£Zªh<|@Ö<©ßÃä|¢ää,rZ<b_ööBßÜ.A,¢ß©ããa,uUî<_Ahðo_Ä,uÖC_vªÖ£O+ÖÐ+:vOårÐÜã>oü.a@@ßaðvbaß£@v,ub+Oä@oBBÖöAüßö|Ö~hhvbuäo/<Ã+£¢Ã¢ß>'), ('Z:Uî/Üãýü<C<bb+CCoä@a:A<Ö:Cv/hzub:ZÄî+£<aO:ý~î~~z>Äzãüvä/Ühý£||ãoå,ªÜ©uÖ_.>ßýbåää|üð/ý.BO:ZCu©ß<£ªãÄ@ýß©vöß:>:ä+åvCBª£.o>Z/*,B_å~AO,rO+åÖZ£>rö¢Ð~ðuö_Ðä'), (N''), (N'ZªC|©v¢Äß~Uh¢£o>ªvª,~Öß@@Oß*BOOöA_¢AªðßäªåaB~ÖABhbääbCÃ_Ü¢A>>vª¢,zBBahåÃ>ÐÜÃÖÐðÜhÄrb*zåðãbUýåZ,*v,ÄU£öbýoO,**ýßbÃv+Üb|Zb:OUöîåßO*:/,'), (0xF502D70F2F74A32894021775707AEE3D8601A0E601FF565636A220DBFE213F3B143FA70B33712EC31501D0202A6125E5EA13FCD7F33991F6AC80D88D53C82A73C3DB6130D3E20914D2DDD1002E352BD57D3AF1EA246748DBADB05FB398A16F4DD75D5D4F00F4120709E704166891C77755030F18D63F4F5C9386822283567B316D8328D0D8DCD58828E9E13C6232731CE9E85D95915676980E01BB7A), (0xB36CD3A8E468F69E792D86F0ED5E12F9611266399BF8E6A0160D90C2D6205B1638642DD08F898EB3F249E4670A66883AFB075A670CB6E9BA853292D7D834C758D270B889304269D884B24751147E95B08456C6CFC6F40A817B734A5CF7B6DBBD818C959AADFF09B99D82E2596F97A6079CE153816DF892DE65370DBDF80DE0CDD689D087E9FB03844C0D314311B012E3CC43BF15635A4F88FAB63475F14CC090A11583E5C61E1DA1DECE3460C64ECDB4252AF0B54DCB697C39488D33C68D93004CA1A2FC2D2C1DAD251E379525EFC1ACE98050C75B0B42D6AB06AB7E91EADA503B331325ABD186F80C42902F94D4564986E14A463DCBA5415ECC5026809E1C3A43E65AF1DC9C0017F957BA187B1341D6AF61F8AFA09412), ('00000000-0000-0000-0000-000000000000'), ('2819-01-08 00:12:52.445'), ('2079-06-06 23:59:00'), ('03:46:33.6181920'), ('2148-04-25'), ('0269-03-15 01:59:43.6050438'))";
            break;
        case 2:
            $query = "INSERT INTO $tableName ([c1_int], [c2_tinyint], [c3_smallint], [c4_bigint], [c5_bit], [c6_float], [c7_real], [c8_decimal], [c9_numeric], [c10_money], [c11_smallmoney], [c12_char], [c13_varchar], [c14_nchar], [c15_nvarchar], [c16_binary], [c17_varbinary], [c18_uniqueidentifier], [c19_datetime], [c20_smalldatetime], [c21_time], [c22_date], [c23_datetime2]) VALUES ((2), (28), (32767), (-5982062), (0), (2.3), (-3.4E+38), (0.4893), (0.9114), (0.7207), (0.4408), ('£åîýÖö£büªü<oªb<ßå©vba©<ü+å+/_rA.Oa.<Ü¢v_/ZÐ.|z~ãÐðÜÜBä¢r:a.bv£/,ðbý~ÃýAv,Ö£Äöb/Az_Äª:vZz,OAÖruUÜå*<>*ýhÐî@.+uðU,ª,ÐÐößö©ÜÃr@üvbo>äãªªðÃ*.üÄöäöB<ð©oã>@ãb.ßÐ©ß£Ü¢:Uå+B©ß©Ã.*üBaßßªÐ~zCu©üAÜrÜrA_Ürb>¢bÐ,vä>hbOäü,aîbbb:@u~î**:a:£ä|ÜA@oÜä+Z:¢b~ßoßßÜzü>ÄÖ~vbh,bãäb@r¢BðåährÃÖaåhýCO_¢uh©,äa:UC¢<z<~~~+obäöðOÜßüÖÃhbAr@r¢îããÜb>¢Ö@Ö,v/z~¢öB©©züî~åUÖCb~UßvöÃÖ_.ý,zO©a/Ã*,|:BCä_zððvåãÐ@Ãð>~a<¢ãB_Cv*hzîhrð,|ª>hðÖ£ÖßU_o©ß>Ð_ã©äÖÐ*|ýªOo¢AC©î+üB/+£vßåaãaö¢_©~+z|b¢ßUöh*|>hßhäUzã.ZOO.båÐ@_Aý£@A©ßCäÐhã>rzÄ*ÖrÃhÄzÃvZOChaÐBÖ/B+ðýB'), ('.~Ã~ßüåÄÐh@ß,*ÃöuÖ>ÄüäabbbBüß*£b+.zÐýÄ<ru:Zª~ðAÜÃüÖ*££Ü_|£>Öäðð,>/<ýAöü_vv<_~/>oA*vßªz:Ä¢ÜO+rÐ+z_îBh<bÐÐarb,oUühh*Ð_£BÜOýÖ+UÜª*|ðoa©z*Ãüª|ß,ub|,ßAråaß©/hðoÄÄ>ü.å@:aUzãß£ÖðBv,öðrý:Ð,:_B>~_,oývC~.@Ä¢_C>O.uvî~oÖüU@ÄAuA/ý+@ÃÜ:£©<ß_äåuZ©äö|üuvOð.ß/ð>|b*,bÐUA:ÐÐÜ~ßBbZäÐöãäbÃbrÄoªvýAÄOîÐO<ß@A£/ußr£>BåBðÃü£Äa£*åAB/oÐÄb.å,äßbuîr/äåã~ubÐOb+Ð_rÄ|hð>örv>BhUªÄaZä:b:ZoÖ>zßAÄýÃ*zäÃ©zÃhÖöýh+ÜÄz£+Ä'),  (N'Bî|ß©¢Ãð>ZßÜã.îbÄ¢ÐÃC:hßýßýo©aB>/©ÜÜB@a@bA>ä_aäðÐZýv<u|£ÜÖßå£_U>r**O£höOªu¢bövvüðb:,aßAOBCa+Ähä|Üa©r©ÃZª+ßÃu¢<Ã>>ãö~bå<.zob@Cª<:+bzö¢bzuÜäArCß|£/@äåOZ<î_vå@¢ß<Ö*uä~oÖå/@Äßuävv@ª:b¢Ðªvbª/.*Oß¢ý.vååðý>Ã¢:,<>UAUa+îOÄãAÐüßüÖ*|uÄBßãª.,~¢ü,ývuß~+,h*ßð/v|UhðhaÐ+bu©,Ã.:ä¢ÜvuzäÖ@Ou¢+Or.ÜÃ_Z+v<:ªuAîb|/îöðZÄA£rüÃ>¢Öî,+OäãîÄhCB~o*ÃaZöÄüÜ*Ã:å>+h<~ªä©Ä|räãAu©ÐÖßãÐ|Äür©Öß£ü~ÄðZ,<Öu|@uð:Ü<h:î>zb~äªoö£ovðäbaÖü@ð|©Öî>rz¢ßBð@brãz*ðaä*h/ã~ö££oîÄßüuîuÐ/|,|ý+ãÄ©uÄAÜÃßü©ª.uðz||©:î,C|ßzª~ð,£z+ß~CðÐð¢.uîäz£>aßBaßå_::ªC..:OÜ:z*u¢£ß.'), (N'bbÃ>vUOßªÖß+ß/ã*h_ßz@Or:<_ü.å+aÄOOã<Üüzårªã:öb>ð.v@v¢@CäãuAÐZßðuÐCO£ª+|orîBð*Ü>.AAaªãÖbÃbü¢|ðªª/©ªÄãåäzbÄ*bÄ.O_bÐ£ÃUß*.ýA@|¢ÐauªzÃUÐb©@oÐöå>Ã,vå:|ãZî+£*rÐß,zÃzÄC<,ÄößabC_@ã:îz£u©OoOöÄüAð@*ähäA¢O,ra|ö£|Üo,ãßåz/oh£>o@oÖ/©aZ©rý©>rv_B£©Ä|¢/Ü*CuArðÃar_<©r<~îð+å|OÄ*ª¢Üz_<öö_B./Z:ýbÐ@ý:üÐªz£bÜÜrÐä~¢Ü£//¢o_v~ö|ßAZ:öZoArU,åa<Ã>ÃoÖßußß_ß|£C+:O,ßb@ªÜzßð~ã,,,Ö.üðÃãCãhzýUÜ£.£A©ÜbaBüüBÐ,*ãu.:/hboÃOÃªb_£Ð@+ýÃ/v_oªZ,©:ýãü<ßýîî_ß¢ªuüãýoa<:U:ÐÐÄî~ÄUãCÜ,ÐÃ+Ähv_Ößü_,brZÃo:Zîur|BUÜå/O©ÃÃär@Z>vaÐðÃ/<O+¢ÄvaB.ãäo@hU<,.C|ovä@åÃarå:'),  (0x86C692589A736BD5D7741E6D8EDC33FC5A4F6C421A5C4C55BD6451787A0876B28E0BBB3043DA32E3D11102C09DAF140B4A7C978D0906B22A793D9B3521F35ACFFD6CDE822103B87F1C897108598BB70E4F452DFE70E9A8885990B6063FFCC1DEB733C230D092EA47C417708094A9D0EE858DE6DCC55B5E14C45629914CB14020C8925126C8873DBC5BE63A597927F3B0C0C881B215E5195BD4AE6C7A6958FA12D74FB80257A925FD4F2980DE21059B9C6278D084308D0AE279F5521A1AC35302EB2781296FF15816A28362EB643A39CE12D017F08876E14A44D589554060CC000EC08828B7), (0x8EBA1C29159FDB52AF42A3AC3A50B9435455115E29EC9B7BD911), ('99999999-9999-9999-9999-999999999999'), ('3033-06-11 22:03:27.832'), ('2034-03-25 03:58:00'), (' '), ('0338-01-15'), ('7589-10-29 06:22:50.6660670'))";
            break;
        default:
            break;
    }
    return $query;
}

try {
    // Connect
    $conn = connect();
    $tableName = getTableName();
    createTestTable($conn, $tableName);

    $numRows = 2;
    insertData($conn, $tableName, $numRows);

    $numCols = fetchBoundMixed($conn, $tableName, $numRows);
    fetchColumns($conn, $tableName, $numRows, $numCols);

    dropTable($conn, $tableName);
    unset($conn);
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Comparing data in row 1
Comparing data in row 2
Insert all columns from row 1 into one column of type sql_variant
string(11) "sql_variant"
Insert all columns from row 2 into one column of type sql_variant
string(11) "sql_variant"
