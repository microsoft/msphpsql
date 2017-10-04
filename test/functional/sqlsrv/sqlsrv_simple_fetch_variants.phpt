--TEST--
Test simple insert and fetch sql_variants as strings using inputs of various data types
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
--FILE--
﻿<?php
require_once('MsCommon.inc');

function CreateVariantTable($conn, $tableName)
{
    // create a table for testing
    $dataType = "[c1_int] sql_variant, [c2_tinyint] sql_variant, [c3_smallint] sql_variant, [c4_bigint] sql_variant, [c5_bit] sql_variant, [c6_float] sql_variant, [c7_real] sql_variant, [c8_decimal] sql_variant, [c9_numeric] sql_variant, [c10_money] sql_variant, [c11_smallmoney] sql_variant, [c12_char] sql_variant, [c13_varchar] sql_variant, [c14_nchar] sql_variant, [c15_nvarchar] sql_variant, [c16_binary] sql_variant, [c17_varbinary] sql_variant, [c18_uniqueidentifier] sql_variant, [c19_datetime] sql_variant, [c20_smalldatetime] sql_variant, [c21_time] sql_variant, [c22_date] sql_variant, [c23_datetime2] sql_variant";
    createTableEx($conn, $tableName, $dataType);
}

function InsertData($conn, $tableName, $index)
{
    $query = GetQuery($index, $tableName);
    $stmt = sqlsrv_query($conn, $query);
    if (! $stmt) {
        fatalError("Failed to insert row $index.\n");
    }

    sqlsrv_free_stmt($stmt);
}

function Fetch($conn, $tableName, $numRows)
{
    $select = "SELECT * FROM $tableName ORDER BY c1_int";
    $stmt = sqlsrv_query($conn, $select);
    $stmt2 = sqlsrv_query($conn, $select);
    $stmt3 = sqlsrv_query($conn, $select);

    $metadata = sqlsrv_field_metadata($stmt);
    $numFields = count($metadata);

    $fetched = 0;
    while ($result = sqlsrv_fetch($stmt)) {
        echo "Comparing data in row " . ++$fetched . "\n";

        $row = sqlsrv_fetch_array($stmt2);
        if (! $row) {
            fatalError("Failed to retrieve row $fetched!\n");
        }

        $obj = sqlsrv_fetch_object($stmt3);
        if (! $obj) {
            fatalError("Failed to fetch data in an object from row $fetched!\n");
        }

        for ($j = 0; $j < $numFields; $j++) {
            $value1 = sqlsrv_get_field($stmt, $j);

            $col = $j + 1;
            DoValuesMatched($value1, $row[$j], $fetched, $col);

            $value2 = GetValueFromObject($obj, $col);
            DoValuesMatched($value1, $value2, $fetched, $col);
        }
    }

    $noActualRows = $fetched;
    echo "Number of rows fetched: $noActualRows\n";
    if ($noActualRows != $numRows) {
        echo("Number of rows does not match expected value\n");
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_free_stmt($stmt2);
    sqlsrv_free_stmt($stmt3);
}

function DoValuesMatched($value1, $value2, $row, $col)
{
    $matched = ($value1 === $value2);
    if (! $matched) {
        echo "Values from row $row and column $col do not matched\n";
        echo "One is $value1 but the other is $value2\n";
    }
}

function GetValueFromObject($obj, $col)
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

function GetQuery($index, $tableName)
{
    $query = "";
    switch ($index) {
        case 1:
            $query = "INSERT INTO $tableName ([c1_int], [c2_tinyint], [c3_smallint], [c4_bigint], [c5_bit], [c6_float], [c7_real], [c8_decimal], [c9_numeric], [c10_money], [c11_smallmoney], [c12_char], [c13_varchar], [c14_nchar], [c15_nvarchar], [c16_binary], [c17_varbinary], [c18_uniqueidentifier], [c19_datetime], [c20_smalldatetime], [c21_time], [c22_date], [c23_datetime2]) VALUES ((1), (null), (-6650), (null), (0), (1), (0), (-100000000000000000000000), (0.0504), (0.5199), (-214748.3648), ('/.Zð©vãÄAßÖðÐöuo©_Ä£öªÄ@£ß¢,Oðua*bª*>ããCvzªuðBÜ|uåîü~¢ÃãÄÜvå£<_BªÐ~©+î©ãÄ~+¢a<~|abozaU:Ä.Ö¢Ð|ü>ßß>£r@COzubvývbßuOÄä~Zrb*ZåvªZövÐB_ã@ã,bîåäböü::*ö._äBî_~.Zð£ã~Avß|îÖuZ,ß©üÄ:hh,ä:ð©å./£raUC_</bZßßob_~ßÜÄ:£åUbî*rböz~aãöåä'), ('o©:îoo/vbýý@h££ÜÃCb_h@î@|hoBroÃC|__ßrüBî<bhböß/+OorÐ.Öb£Öð*Aa*bîzåüªBä~ÄAObB,ã~ÜÄßîbööÄß<åAv¢båCýuÖbßãvz~>ÄuÄývð+'), (N'vvöªß/îa©î++|>üªßBÄ¢öªrÖßC+<OvÃrv.AhÄÖO+C~Z@~bÖ>aß<~ª@£ÃbOÜÃb/ã,robo:ãîbð+>,zÃOÖ+ä<,ßCªÖÖ+b@ü>ZÃÐîz.ýªboî/£uAv++aAzOü<~B~z.BÖ,b/bß£.,ÜÜÖßZ<+<+Ð>ýÐ@öOCÐUzrãÃrö/_*uou¢öýuU~,©ar¢r~ðBAähUüb,BoöB|äå<ýuäðÃb|:böä>bäÐÐu__ÖuýÐßO<vZßru@~Ã/ßö+B@*_UAA©bãäåbAÃßUa@bÖrÜv£åÄ+î+/ðÜå/a/ÃÖÃÜ_.:/ðorßÜ:/zî¢åÖbî,zÃÖÄ/ðBOª©,|Ußvrß*ãÄÐ@_h'), (N'¢åU_v+ß,,BÜÄa>ãüªo,îO>¢.b/uO©ßh¢Ü/zuöb,AÖå:O/Bz*åÖî,ÖAßCUã<äh¢Ä~öoðªOªA|Ü*hZb:ýýCåZîîÜäÖªOýBª_îhoäuvoÄoZÐ+ª.å_bßä<ÖUzß©Ozoaý¢ðöU:aCOrÄß.ÐaO|o:åbBOuhß£ã+OAüýÄoÃß.*üä/î~h£*_Z£CaäZöå/Ãß<ßOÖýoÖßÄ~*ß/>a@ÖuUÄå¢ßäB_äßä+ßou._|äßCÃz+ã¢öoBaUî£UÄ:Uªßý@zßhýßÄÜ~Aö<©öC|,@ßOîö:ã|üÄ|:ßhöÐäzßîO+aðO~bbßUÃhhbÐß£b|åö~ABozÜåýÐß£z©roÜUÄ'), (0x3F69A37E16303C7AC955661D1BED304E9674FA57E87BF1B2B85E7F31B75D57EEB7FAE5F97FA9E7E77C921B2910D481C88E564752D3FDD5C477F1C5B8B10AC36CFD7765210837CEEC8D12DB555FC8A1E4DDB6A26016051BB92421818DE42F3671CFAF2C996F5FC057885AC5C1227F64AF4FE1DAFA686256F75BACFCE7B540085DDB6A85B09B08747DF64BD8BB405A97A5BCDE9E72E8EA6D08E46AC42909973DB63CA2E2EB3A6E63B604), (0x0F), ('00000000-0000-0000-0000-000000000000'), ('2326-02-20 08:51:23.203'), ('2024-02-29 15:02:00'), ('12:39:54.0255300'), ('2001-01-01'), ('2924-06-04 08:59:21.2768412'))";
            break;
        case 2:
            $query = "INSERT INTO $tableName ([c1_int], [c2_tinyint], [c3_smallint], [c4_bigint], [c5_bit], [c6_float], [c7_real], [c8_decimal], [c9_numeric], [c10_money], [c11_smallmoney], [c12_char], [c13_varchar], [c14_nchar], [c15_nvarchar], [c16_binary], [c17_varbinary], [c18_uniqueidentifier], [c19_datetime], [c20_smalldatetime], [c21_time], [c22_date], [c23_datetime2]) VALUES ((2), (0), (-16753), (2098337643), (null), (0), (null), (0.5371), (0.1049), (0.5799), (0.2674), ('Ub£b*u|+ßCî<Oo©ÐåroUB/ö_ßÃö_Cüb:öhýö©ª.uÐvvzubÃöaä:bÐýOãhCu©vauãb<_AOÄbvßð@ãh£zÖýa@v_ýÐüöhß_+ÖöÄA/UÜ_©ÖZßoUß<+Ã>ß>©v@üzbÐ~BAî_ðbýßßÐ,|u*Ur>,Öö>ª:'), ('åb©rbCOAü|Ä.UuOß©|üCUv*aZ>ð*ßb>~~ßbýz~Ö¢r@UÜ|ßuo~oßA>r<ªÐ©ð~OäUåbhß<oO<Bß+îÖa~<<vÜÜ¢v:B,z:v©©o/+v,Ã/ýÖý,ÃÐuhZÐªhr<z/B¢ª¢A:oÐðß:£ð|>¢åb<@ß,ö|uöb~ãC<räýv£Üb,|ªv<åZ~rÖUýUhýß£ÄÃb.B+~åß+UßðvããßÃîî@ðbU£Ö**ääzrb+~üÐÃZrA,©_bßv>.h:<båCß¢ßÖ*AuaðAAÐ~ÄåCC.ÄåhU_rã<ãöÄ/ãhOUß*ððöOß+Ð>abÄÜ_Azb+aü_BªðäOZZ@rî|zð_rr:_BÜ_AOÖÜb.ÃBÜÜovUU*abý>buZC_,äýãC£ãîrab.oBCå¢.:C,A©ZäO,_ªÄZÖbu,r/äýhðvbUröãýå¢:ßäýo*rÄüðUZz>ÄZzöÄî<Ö/zrCü.bð*,£üv<åbbAaÐîbå¢ö~|î¢îvýoÄ.|ßA,ß¢b~å£*uu/uä©åÐã+/~o,äOZ:C©ß|b*ZA©uBä<ýýª+z>,bªB/äACuåýö/ÖhÖý_©oböãa©¢ýå/ª'), (N':Ö_Öo>rå¢CÄ+Ä¢_<Üîüã©ÖÖ£Üv~ª/Bî+h£vzO|b~CÜ¢ßö£u£ðÐÜh£ªªÃaîýîðª<_u*rb+uZuß.ÐåCüÃCAöÃÃ*ÜÐ_A~zZ|O©@zB|ª:hvÄ:_zUåÐ~bråäüðÜO:¢©AÜða©£Ðr~'), (N'©î©üUß<>©zaUbÃ*Äª_ðCýßB@ß:ä©@©o|©hröZö_,ýCa'), (0x00), (0x7F706F21089D4564F6AD294C130B9080F17FA23DB5F0F5E226DF33A8FA7B4E37A6FB84AEC130BF9FBC510599E6094F3F8C09AF0D8BB5278F7B8DFA28699C697B5FF1A51887E1BEC0C0028064EB5BFC1C8FF31BF0CB40ABF1D3F533343597351FE7893AF46336B8AA4BEF4D8935C2A42090DE98758179B01F45B44591AE8A8A29CD617D612108B9593B71A5DBF222BC105113A457FEBF9E6DD81BAEAFF126FCC06424BBD34542FE88243F390FD4D42C22F834409BE6332A0EC20F88065909671574E477CD67E8CB0E141B32BE858E9903EE8CB7BDE560DA31F9AC1135B9F82F1BD7249BEF5D5FBB0F693187CA006C8934768417CC52AA30140A46D3BAD795551BEC17E661F3A9A68ED1A754B3D90F308D23DCBF34E062C92DF386E5178C0038D3DC6CDEE9862740CCDE89FBAA567D3AFA154696FAD5203A894DB96D6CC48E02D06E80D66D314707FC9CF35657661ED6764CEA02466A3EFEBC32549BCCBAD30750862B3BD6A6CDC9CE3E9A5EE2C9E2232EE2F720), ('95850cf7-8b0c-4f61-aa3e-25eac6efe46c'), ('3058-07-22 07:25:00.198'), ('2007-04-23 17:42:00'), (null), ('2016-10-31'), (null))";
            break;
        case 3:
            $query = "INSERT INTO $tableName ([c1_int], [c2_tinyint], [c3_smallint], [c4_bigint], [c5_bit], [c6_float], [c7_real], [c8_decimal], [c9_numeric], [c10_money], [c11_smallmoney], [c12_char], [c13_varchar], [c14_nchar], [c15_nvarchar], [c16_binary], [c17_varbinary], [c18_uniqueidentifier], [c19_datetime], [c20_smalldatetime], [c21_time], [c22_date], [c23_datetime2]) VALUES ((3), (141), (-26849), (9223372036854775807), (0), (1), (1), (-100000000000000000000000), (-1), (null), (-214748.3648), ('.å|ãð|ÄvÖß*vbb_ä£Ãß£,¢>bÐªhUå.BaÜäðZ>ð£ý,ÖOÃ|_aÐ£AbüvCzß,@Cz.<,*Äz@OoU+Ðbßv¢/ß>Oª/ªä*CÖ£_hîýßzªî,äýUaß|.©+ð:ãÄ+~aðÐýå@ý~|ä©a++îÐu+U|ãUoÜäOrýOzßãÜzýåªªaää.Äho|bZ:Ou>:ßÖÜÜüCÃza.UbßUA,Ah¢*.Ä:_aªzhîrCÃZ/A_ü*,B©:¢ßCß@.OU_*/'), ('h>ª.ãîbã@£Ch©:.ÜÜCåß©£_Bß//ß©ßvÖBß,<ÐüvÖ.ßZ<©+ÐÐãÜ|ZÃ>|@ü@ª.<UoÄ,+bãbðAßãC/ä*b/:ÄÄüîuýbªUå>r,ävo¢ªBZzå©îÐÖ:|b'), (N'ÄÜvv>,Z:~O/ã/ýãvUÖöCCßAbO<_@_|Ä~Cuo@å+BÄaåZ><î*<Ðß:/üüaåÖuBaäßðA<äÄOß*,å£uC'), (N'|ªZCbßÄBvªhrZÖ©vbäröva¢Oo©./~ßÖäÖ+r£ßåßauðOU|ü_~Ð¢|,îzU_üBü.,v_>äZ:ð|A<OArÐ+ß+¢OhßÄöu+ö:C©©Äî/¢ö,Ã.v@ªaÄo+*ÃªåÐª<ßU~~~v>*aßî+ÖZ¢ªßýCü+©|ßaßäAväuðbuCob,ß/îð@ÜC*.bÄBßî¢£CCÄÜ|ß/<boªüz@o<|/OÄb/oÜÃÃBÃv<OhäÖªÄªåbÃvüAAuz:ð£+~U¢*©å,CU+><Ö*£ßß_ý<<arÜ@ohöBu/Obîîð@_bÄ*<¢ÄrßrÄ./@îöoî~bäß@Ö/AAðZ/ã/Äzv<vhý,@~Äö/|ÃÄ_Uîa*å©Ä|üî:~Ü'), (0xCBAB9B63B5DD23C02818F80CBAA04EA41ECB6FF7ECAE2496171660469E3F731330C949AA10D861D6C664D1DD9BA04020C9319EF2D731B31D86044C9F7DDD22E8A4E4DCF057A394456A993188DC46FB76D6407C6DA0813CFF152B240B8CC258E840533413A2CBADF2B65AED914391E0200BD43466A5132FA7BC787CF0D781DCB222F5C7E195449E4903EBD678B488C951226D9C9BC1BCA8CC238D562A4EBD1B357D3B6F4ECAFE8AC735), (0xB93DB7F234AE2BCD69E441F0654CFABE08EE74FB46D0A1FA79197B8623E2D565D759EF8F6B9A227B23EFB1503C4A09042A425F30FDA463EE140DC816C4CB4BFF4B0ABAEDC627123831CE5778A44DC4B05912F30E5EFE13D453F44BEFDBD1783AACDF832507603748E52613BB4B4F2CBFE48B643CFF3BA4F944435F8FEC80B19322A2459268BB6A705F48C323B5D462CA8F14D9B4714E5B013B8AE1C400A85B65758099A80999572921), ('cfd87d03-0db6-4e6e-989c-2a0459660bb3'), ('2313-08-20 10:52:29.861'), ('1900-01-01 00:00:00'), ('02:44:51.6130224'), ('9999-12-31'), ('1400-02-25 11:23:00.4519650'))";
            break;
        case 4:
            $query = "INSERT INTO $tableName ([c1_int], [c2_tinyint], [c3_smallint], [c4_bigint], [c5_bit], [c6_float], [c7_real], [c8_decimal], [c9_numeric], [c10_money], [c11_smallmoney], [c12_char], [c13_varchar], [c14_nchar], [c15_nvarchar], [c16_binary], [c17_varbinary], [c18_uniqueidentifier], [c19_datetime], [c20_smalldatetime], [c21_time], [c22_date], [c23_datetime2]) VALUES ((5), (76), (-27503), (298552558), (null), (1), (0), (0.8696), (-100000000000000000000000), (0), (0.9480), (''), (' '), (N'ßßuäßuîBüã£~¢îv¢ãäz>_ß>aaOßåö.*OªÃý*üBhßî¢Üo@üAîÐß~|ýBUa*rã¢/Ao~>:üöîð£ÖÄ@ªðbz@AhîhCãÜr>:ýaÜU@,å:r©býBaßoßä<@zöaÄß>@åAÐÄU<>îîå>OhÐ<ä£:bAî*öaðÐýhbab:Aäý.<üß@¢BåZzÃ*ßãB+UÖ<¢_BÐß@>+äîßbovÐb.Ã£_.ß:©Ãu.OZü£ßÃ|U<zÐoZavUý>v_bbÜÃÃ¢ubßb:A~CzbåUý+ªavÖ©_*@h|<o/Ä*C_vüO<>:,åÄ~OÄr+Ð@îðbßAAhZÄîU¢vªäÜr_¢ZßîB~îäüöoßörÐ£_ÜZz>UßîÖ~OÖªBÐ.@©Ö:Ðaß>îObOCîübå£bb>åb+<åh_,hUäuÖZv<Zî_üß/zO¢ßã¢håüA£ð¢¢,üääZBÖðrü+ªÃª~Ö|CÃ:ÐoãBZîuUZãªãÃ©b*b©©Ä©.ý©/uð|zr<@uOårCÜ|,å@£,,_ðA@uÄå:ýÜ*OO©büÃöb+bª@ÜårvCZãoÖªZåzîAb@~bU.|aöB>.O||aZð/U£A/Üoo¢~~åßÖ'), (N'å|Üa+BuävO¢å£¢rªýüå©,bBßîBßü:bzÐ*@ÃýUv~zAßÜuoZ@a+ýåßä/vã<üBUzU.råå_Cðu+Ä.|¢~Zå£¢ªßª:o>åðuä_vOzaÜ,|+vÖ,bh£o.ZßÄ|~*>©_£üA,a¢åbÃb_B|ärÐ_+ärÄ:ßaB~C_zöðÃ©<C/_üUh>ý<|¢zÖö>ßbü£:ßOoAZ:>+ÜÖoä/BÐÜO|BÄÐÐýU,ÜßOA>åübu£ðv@Ãß©hrÄ.Üå£uÐ£@äðÐBrî©@¢ZzZäOßr:ß@ÃåZb~@väªåOözã,BC>|Ð,äoO+ãðäröÃr:rö£~åhªðAb¢:*ý:>bÄÄîbhãÄBÃÐ~/©£rü@CåÖ_u*öß*ß_Öaä<,CUüZ,bCö<r.a@,./<ãä|Ö|<+öUßao+©åCb_.U©CbÜßbAööZü.CvOýî¢~Üª+hvBÜC__å:ãýOßÄßßö>:/aöã¢AB>îr/ü¢£zýÜ©Aü>.bãC/ð<<oCÃB*o*O/>Ðab¢ÃAå,_>ÄAhð@öaöBÄOB¢aÐBÖåv<ýß+oZö:ß,ßUA,ýÜhüÜ~.h,bÐz¢.Aî~výBb@ðÃý£~>'), (0x00), (0x92BDFAA6637CC331324265EE0D819DB02A8FE57B5001480073A02C4D470E99DDEBE9B2FF460387BC3F55A4E997E9C3640605ADB1BA3DA99175F933C24F75EA23A9870224CF9E692608293CFA5C5F6989DCC7474161E723AD3F218A4CD0C46CC96B813F0FE69F95679B86A99001AF341590A9E5872EA8C2C16455E5AD141C5643BC728FDB1F5AA33D32DFA5DA3E92AC90ED74BC26DEE49BC2BE7EAA91B9E7537F2F65D48E2058F53605A8554E02E783D1A954C09F603065D7D7CD1CA1AF12834A8B26ABD8BE1D70947F8620368F4CBFD7FAD9E76427B41FFF00FCB7523B5386F3240AC2DC3CAB128CE42B1FD90F2E478A0D45A7D52A1E23D707ABD7C1651D592CFF2D11858AE39B4FC65B80FB9545F0D1B42D45F6ACB0C805E6EBD490D35026C7D2F3D9E11DC1FF2AA9580FCF9E2741C2BB050C37), ('55a1f242-dad9-4f8e-b839-364fb6e1ffec'), ('5256-04-02 18:20:54.032'), ('1900-01-01 00:00:00'), ('04:36:52.8132961'), (null), ('2001-01-01 12:01:00.0000000'))";
            break;
        default:
            break;
    }
    return $query;
}

//--------------------------------------------------------------------
// RunTest
//
//--------------------------------------------------------------------
function RunTest()
{
    startTest("sqlsrv_simple_fetch_variants");
    try {
        setup();

        // connect
        $conn = connect();
        // Create a temp table that will be automatically dropped once the connection is closed
        $tableName = GetTempTableName();
        CreateVariantTable($conn, $tableName);

        // Insert data
        $numRows = 4;
        for ($i = 1; $i <= $numRows; $i++) {
            InsertData($conn, $tableName, $i);
        }

        // Read data
        Fetch($conn, $tableName, $numRows);

        sqlsrv_close($conn);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    endTest("sqlsrv_simple_fetch_variants");
}

RunTest();

?>
--EXPECT--
﻿Comparing data in row 1
Comparing data in row 2
Comparing data in row 3
Comparing data in row 4
Number of rows fetched: 4

Done
Test "sqlsrv_simple_fetch_variants" completed successfully.
