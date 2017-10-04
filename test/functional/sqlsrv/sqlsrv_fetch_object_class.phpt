--TEST--
Test insert various data types and fetch as strings
--FILE--
﻿<?php
require_once('MsCommon.inc');

class TestClass2
{
    public function __construct($a1, $a2)
    {
        echo "Constructor called with 2 arguments\n";
    }
}

class TestClass3
{
    public function __construct($a1, $a2, $a3)
    {
        echo "Constructor called with 3 arguments\n";
    }
}

function FetchObject_ClassArgs()
{
    include 'tools.inc';

    set_time_limit(0);
    sqlsrv_configure('WarningsReturnAsErrors', 1);

    // Connect
    $conn = Connect(array("CharacterSet"=>"UTF-8"));
    if (!$conn) {
        fatalError("Could not connect.\n");
    }

    $tableName = GetTempTableName();

    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_tinyint] tinyint, [c3_smallint] smallint, [c4_bigint] bigint, [c5_bit] bit, [c6_float] float, [c7_real] real, [c8_decimal] decimal(28,4), [c9_numeric] numeric(32,4), [c10_money] money, [c11_smallmoney] smallmoney, [c12_char] char(512), [c13_varchar] varchar(512), [c14_varchar_max] varchar(max), [c15_nchar] nchar(512), [c16_nvarchar] nvarchar(512), [c17_nvarchar_max] nvarchar(max), [c18_text] text, [c19_ntext] ntext, [c20_binary] binary(512), [c21_varbinary] varbinary(512), [c22_varbinary_max] varbinary(max), [c23_image] image, [c24_uniqueidentifier] uniqueidentifier, [c25_datetime] datetime, [c26_smalldatetime] smalldatetime, [c27_timestamp] timestamp, [c28_xml] xml, [c29_time] time, [c30_date] date, [c31_datetime2] datetime2, [c32_datetimeoffset] datetimeoffset)");
    sqlsrv_free_stmt($stmt);

    $numRows = 0;
    $query = GetQuery($tableName, ++$numRows);
    $stmt = sqlsrv_query($conn, $query);
    sqlsrv_free_stmt($stmt);

    $query = GetQuery($tableName, ++$numRows);
    $stmt = sqlsrv_query($conn, $query);
    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_prepare($conn, "SELECT * FROM $tableName ORDER BY c27_timestamp");
    sqlsrv_execute($stmt);

    $metadata = sqlsrv_field_metadata($stmt);
    $numFields = count($metadata);
    $i = 0;
    while ($obj = sqlsrv_fetch_object($stmt, "TestClass3", array(1, 2, 3))) {
        echo "Comparing data in row " . ++$i . "\n";
        $query = GetQuery($tableName, $i);
        $dataArray = InsertDataToArray($query, $metadata, $i);
        $values = (array) $obj;
        for ($j = 0; $j < $numFields; $j++) {
            $colName = $metadata[$j]['Name'];
            $value = $values[$colName];
            CompareData($metadata, $i, $j, $value, $dataArray[$j], false, true);
        }
    }
    $noActualRows = $i;

    $i = 0;
    sqlsrv_execute($stmt);
    while ($obj = sqlsrv_fetch_object($stmt, "TestClass2", array(1, 2))) {
        echo "Comparing data in row " . ++$i . "\n";
        $query = GetQuery($tableName, $i);
        $dataArray = InsertDataToArray($query, $metadata, $i);
        $values = (array) $obj;
        for ($j = 0; $j < $numFields; $j++) {
            $colName = $metadata[$j]['Name'];
            $value = $values[$colName];
            CompareData($metadata, $i, $j, $value, $dataArray[$j], false, true);
        }
    }
    $noActualRows = $i;

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}

function GetQuery($tableName, $index)
{
    $query = "";
    switch ($index) {
        case 1:
            $query = "INSERT INTO $tableName ([c1_int], [c2_tinyint], [c3_smallint], [c4_bigint], [c5_bit], [c6_float], [c7_real], [c8_decimal], [c9_numeric], [c10_money], [c11_smallmoney], [c12_char], [c13_varchar], [c14_varchar_max], [c15_nchar], [c16_nvarchar], [c17_nvarchar_max], [c18_text], [c19_ntext], [c20_binary], [c21_varbinary], [c22_varbinary_max], [c23_image], [c24_uniqueidentifier], [c25_datetime], [c26_smalldatetime], [c28_xml], [c29_time], [c30_date], [c31_datetime2], [c32_datetimeoffset]) VALUES ((-596308883), (0), (1), (-856437494), (1), (0), (1), (0.2409), (0.4671), (1), (0.1303), (null), ('zöhUa</~î<îC+OªÄbö~/oÄ©å_å£üb©ãÄÄaöA<zÃðÃßo>rCßZOÖ_O@+Ö¢ZvÐCã<_u£O/*U~uÜv©Ð,_oÃ/B+ßzC£O*UzÐrª|buvðäaÃ:bª@Üðä¢,BhªzA>©uCýýbBðäAoÄ~@BªZ©Ð£zOß*Ð*ÄäuªªZ@ª|bßåaª+CÖ>ªuvCZ>ãð~.O>/ÜU©,uÃU/åOýöbÜ.©CvO/zÖ@bª~ÖÖÃÄC/b£b¢*uh:@bÐAZOUÄ/U<CýbBAÖ¢Ü£ð>Ðob|ä.ÐÜzåhÐðva<Ö.*A*/Ua/a|aåÐ:>@az~üÖzãý©ð~üvhãAvä+zîozîbCãäÄîª/©rîö/@aB>b©>ÃAoåÄýªåBO¢äaÃî¢<üvÖ*hzbuåªÜävßä>ßrAüöhÐZÐ*>ªböÖ¢,¢_Üðo|ÐB©ã.ãAß,Ö+r<å©_ã|:¢ßã:oöãh<©ZB~©©ZäZzz<Ä_CäÃÐZÐÐBÃu<_Öå©>å|Ã|ob©ß_ß~ßU@uýðvÐª>r_/Ð~ªäCCî.rß/ÜÜh+ArîO>höãOa<¢>obaÃavßª>ß©/îOª:B<uÜîÄ,~abvßÐ.ä£>.'), ('ÖÖ@zÜ_.uÜªü*,B_uäª<Ã:©üýAäböaö>ö<Ã*ÐÐ<Coý*U:z:*zz/£ßÜ:zBÜ@Zî:<rßBAåÄÖß@,<ZÄB/@Ã~_Ü¢ªZ©oa+UöuouÐ@C/*v~£~@ðU+>Ör©UßÄ,Ãä¢@:åä*@äZuu~OýýO/ýîrCÃbzÃUh~Ä.:ãb©@îî.ÄZb£¢ACð~uÃb.Ð>>Ðð<CO¢OðoåÜ,@@>bvavªh>bAOÜÃðî¢ýÐ+~ð~:ÜrBCh<ÖUÐ.bÖªß,:å+Ur:Ö@å:ÐvöOoîör.bA,ªö/ãvv£hÐîho,:.>Ö*~/üüª,COh,îÜî~~~<ß.©OÜåÐÄßCßßÐ>:<b>ª/£*AÐbCUUüý|~ßý¢BBhß|uvßU*,/å:zãBýhß:+ðB£¢>Öß~rýÖå*+ðÃAr,|ß.ÄÄ+ß©o<~©ooÐoAoðÖ@ü©C,ÃýCöî.öö:î|@.ðvÃB©C¢*~Ð¢Oî@C:äªCZza+ÃÄvb£ÄÄUo£¢U©b/z|>bCv@Öýîã@ªz:uBîîý,,bý¢ä<:|'), (N' '), (N'Z|ßUîBOüvoßrCöÐ/_*ÜbAð@>:UßbrÐ¢,Coaå/ãaßß/Bår.Ö@ÄCÐöÃz,+Azß©ZCÜ||/BööãýÄzüv¢ß£ZA@Z+býÃÃßÜ©ãCa*>©£_zÜzß~ähA¢ÄAÜ_.aßoaÜÃ~åÐZoZaäÃý<ªüZz@ÐÄÄ>ööbýÜaC:CuO>îüö:ba¢Zäv+£u/ßUb>b:ZZÖ£ßÖ@oo|Ð<ßýÄa*ä<ÄB/¢©ª/Ö+uä+£hÖr¢rz£z@<CZ_åãUÄÜ'), (N',*ä©Bb,ýoðZOBzbßBßAuhO£ZzCÄ~>£:öoªÜ.*öCUUz*_ßC:~ß<¢üåýåOÄ|*ü+ß@_<*A>_£:hÃÜüßäB>rÃCßAa>boÐªÃÜ¢hî:ßßb<böAha<v'), ('z¢bCZua<@ýZåÜöü©@_ä_ýbAahÖB£a_röo'), (N'Ä@äÃ/+ZbOrobO*öÐU>bv~bý|C,:üÜ*ÐCÃîüaÃOB£Öüßö~a<©ª>Ã©ªa@¢_z|bîzzä>b.Z,öbb,/@ªvAüBðÜ,Z£b@©.*>,¢bÖ:©ö<@¢oZ£/£Zo>î+>*:hCöAr:_ÐîA:Ã/*Ãböz~<>Ö>ÐäaÜ+O>ýaAOî¢>¢ÖAüåvvrÖrö>/öbîåü,£rorÃO|.A/£U/b~z~|>ª>r@BßßäÖ|ðBor_üZ:_+ß*äuÜCö@:ä¢>+,,î:Ãb.ä*ßäB*<bîbväArÄU¢üÖýåz/îð:Ößã£_ª,h><_aC:£å¢+~Ö:hÃCv<öB_|'), (0xD35A6A86054875A44046409962581502342C6344FD8635EC1395F8357448F3F191003B3130746C9B9EC07B4C32D764F9AA94050A90AB2A4D257D736F4F4030F8534E07621DE4D1BFC0C8A53C0AE8BA3B31FE8AE042E961E5D6ED0BB201FD4B5E9083F9E3CF02DCC6C3464AC806EE423DC3373E51B64FB56AED218C30D81EBDC37C46AB87EFD66B2E22C41CFC9E98DFE7AACEEFC9BEFAA840CD8FF5AD76ABEDA488874BBF42BB4EEDCB0F2EF133B9CB5542A485092FF404829E6ABA60E0BEB876D67873174C1FA5EF8001FDAFCBB60D13C3A06AD226E3759382C821D800ED0C0D56B81BBA45B30823FE96892DF654B7E85CA69C3F53E650F203DF9484FECDEE92F5EF59139A52CE109847D3905A479C2E11387B5951CDBA706728B7651E842431A7F63807FBA34D0AD88CA60B13C098BB426672C55194412735E9AB729A68F11422A1E8630E95336DEB7181C526AE5CA790FC23B8115FDF12B510D112F8DB6F709A92E971D734ACAD3304DAE0137B01868C03E19E453903C3C1D6AEFEC421751001112B93C5EBD05C4F75840C85FEFF3A0E4A85F4FAD02B608F26AF0185AEE9128FCADE02506DC1A033844ECFB4F63EC3D774EF799B801631E5CA049F3BCED6A08C727DBA20FCE9A189E4C075815BAC98E8EC2A11BF6F760EB4AD1C8668226465ADAD42417883A3DA8A4EEFB6BFA0AF8A1D6BB64FA4C5E11CFC6F0672E0633866), (0xC7D3196A20644950877180BF4E13FE9268FD6B15C1E8CC2545097C30F92715883664D8DB70B34B5106533E6F9C3C20F3744EFA3AFB6A9ADF07F42517E818565F5BF789EA3B2221B5B1190F6DAA5C8808E988BCAB2DDA8DCCC9F8D37C8E3469EDFDDE14A4B335B8512C4489E15B2D35AA6B1C2D5F5CAB32806ABD21424EA5277DB02170CB48E355B3D06B7EE851AC709CC329117106099151AB933AF6147502871D0C3039BD696287C67A8FF12D4A2FBD4C857E2594B576A7901E1FB4A85F9F5A68F7CB457B37DE59C689AB8C79A02461647DAA8FF6DCF3679589A99F4AADDE67DA3488367FE94214A6255F0A0AEA2DF0FABD502BB510638C0463B0F28E3F0F18AB44B14517EEE27B3A95E8D17DE85DAB5BB8A48C5DB2B1CAD0FC321B3950866715465EC538E8D80BDBD5F11F27920E1B060496D59A83BBF992E6741BBCC741EA0F81BB3789977273DF99B188C092A23EED161434B9B0CC928A65335173BD4F13930710A6D6D7916F9223F501D233380044352DA5B20D305EB429AC246320FF22499DF4B982FC681685A5A196BACBC0572D330D3E90A96186D7B1F1AD2A6D0C7224D3B7353E7E7E47CD16200D6B99A4E96855F73012C0929F3B6EE2D00ABBEC17992F72E7138B19AEB5C5717989531B3D0D0844D576DB5F634018F9CF9CAD3D112A08743B27BCD6DD3B487A4D4F1916E853A4C3027716A1BDD00EA6FA92491207), (0x80DFDF16503166992D6112132702117CF7E93B9A1466B76A1816DB4BF13572C4F0CA9508738E73D0107C745BBEFB7EF880CD1B1D4DC43A1A2875FE39551BA8F7EE4D235D6C080869766A8A97F25BAC1C97E3CA241A55772B907BE0FE8F397BF4D47C6D71D8218B78C5B2A7E23AA227CCC9E1A2725184DD99D5F7CCF1270AD93DEE95C376ED3A447557525F68AD95F0ED531335AE9609A3509ED49CE0549A50BAD969F4A5DFB1DF8E9D2C1AFE2575EB4006DF9D13D42AA6E5F173D7199E6320BC01A48F44A14B0EA234A61169EEA42A29E95CD975FD3CE6AEC30EB36BD9C5F0390C5EFD81E5A69FBBD1FADE2D193CF74C48AD2F418AABBFC4BEE1819297A2C96EB6D85D8FA7A94342BD1EA4035DB894861666232B96AEAE619F38C304250C232E306F7741DF67A59E6AED846B06BC67054CFA7B307F9DD80729FCF06FE63E1CB0FA75D2ED4052A7AFC184C1A829435AE3D1BDB82B90F2AF92976F18E37736B82BD0D37091E979C4FA53CC6FFADC2FDDE5A10D9D2986F12008C63F56D13AC3378ACB96D756D2C9F957F8BDD7C382B4B66791324169988FA4DFB368CBAA22F3C08F95E52E46588713D8144530C8195D851189EF3F0782CEF23CF1F8A26AFE4E3505FD472E9D856F0F091F6955525EDBC656E2B92133B3B80DC6F2F2555FE4A8D9C5102BCAD12EE78B19E035E719789FDE15324C87442D0251453A67AA931AAF25768F8F423210F427FB2E07537ED1F34D53133F2D982195970EDA8597C63CF7FE3010708AE85CAB043D258B1625AFD27447195680DB818D55FAF834588B5C60BE3609226D606865F7683AEF72957E725E071150ADE57722AF831B90F0285379E1C4CDDF1C8C6513DEB45F17A0911ABF7B86D5507A0CB3D8BFE672EA44D874CE14C10EAEC79F55D1A2FD71F8865F821D695B156A5302FFB09B27E324E6C58CBA56A387FCA7FE715972416FA69363), (0xC648FDACD01D6A0B6039F39937DDFED9AE5F8F380AAFF91E79E5B3D32FCE1C38DFE25E9EC7E0EA38387B9C6E0AD821954295F833D30FF6164EDE32EA6A96DC911CBAB9557F6012CE7557FF8E138B14A1C31E9199D6E865CFEB484FE9ADFFCDA29E0B514F1ECC16E1832ABE7C0F6703C3CA90274B0542BB3455EA693610282075895DF00739C583F608AF15AC2AE8BF83458C1E1CD8829C5B501DF93362DFD15A0948F3B277FB89040D3043552C0E096D2AE5F1830A049D07C094B71BCB4F9328DEBBA3E1F50D0C4A031A0FA73E472E41138C097DEE4D5005ABECF2E3436D38BD9C1A77716B28771DD9A680F0F4F5C8796576083F46163A916A9D35091A280795E1520A23D0BDD6EB14EBA347CD6010B523797ECD65F97686E0E606CDF26E02B09FEA0EA2EBA5A9A79F1C2A5B72F2C30A8D7FE5A1AA3A30E7DB8442EAB608F8A6E8F3A8E2516ECA7DD0D62D48CF1B13E22FC407388610565B927C76662BEF1972484727F4AE1B81EB567D1D40CE717BF7054C8B9C7851D87ED422929A7641B11E495244BA994A4404321BD208F3058D10F07F50EF637CF9F66EA9107B78E77504CC0F077C3CC16DCB08775A6C7FCF8BCEA57199F6E0C1AADC584E4BF029C89B5A96DD3CE40E10E43F1185E6FC129D869F1D41D6074D8E240A643B13EE756DA13FDC0FC5A28297B0848D6AE55AEAAEB9BAC7BCD2D7E2840D18A9328FD2FCF5C9A3E4A71002D7D41C75914179E298D92FC5715926C89AC94D5802ACF95017DD8797A14166338EA6E2E3905D46A10A1A7FD012CA619E397E3B475B39876CEE7F9F967842190279C1528437A4109EA2B5ACDA29D349C43EC30E5FF8C9B2088CAD860DC94576EF50B6D50D4C4A66956D9CA4A68CAD421B35DB58B434B90AAF0E49F78A1D02D55D3FBC8973FD3C77F45D78F25B250C7CDF6B6BD8EDECC1BA96AB26FE0352), ('9d30ba7e-f6f0-4aa7-a7b1-bd074f458078'), ('2016-10-31 13:39:46.362'), ('1938-07-12 22:15:00'), ('<XmlTestData><TestDate1>10/31/2016 1:39:46 PM</TestDate1><Letters1>The quick brown fox jumps over the lazy dog</Letters1><Digits1>0123456789</Digits1><Punctuation1>,.;:?</Punctuation1><Brackets1>[]{}()</Brackets1><MathLogic1>-+*\%^=</MathLogic1><Symbols1>~!@#_|/</Symbols1><RandomInt1>0</RandomInt1><RandomDouble1>0</RandomDouble1><TestDate2>10/31/2016 1:39:46 PM</TestDate2><Letters2>The quick brown fox jumps over the lazy dog</Letters2><Digits2>0123456789</Digits2><Punctuation2>,.;:?</Punctuation2><Brackets2>[]{}()</Brackets2><MathLogic2>-+*\%^=</MathLogic2><Symbols2>~!@#_|/</Symbols2><RandomInt2>0</RandomInt2><RandomDouble2>1</RandomDouble2></XmlTestData>'), ('05:35:51.9401724'), ('2016-10-31'), ('4685-03-06 12:32:44.5405972'), ('4052-05-14 01:15:44.4184521+00:00'))";
            break;
        case 2:
            $query = "INSERT INTO $tableName ([c1_int], [c2_tinyint], [c3_smallint], [c4_bigint], [c5_bit], [c6_float], [c7_real], [c8_decimal], [c9_numeric], [c10_money], [c11_smallmoney], [c12_char], [c13_varchar], [c14_varchar_max], [c15_nchar], [c16_nvarchar], [c17_nvarchar_max], [c18_text], [c19_ntext], [c20_binary], [c21_varbinary], [c22_varbinary_max], [c23_image], [c24_uniqueidentifier], [c25_datetime], [c26_smalldatetime], [c28_xml], [c29_time], [c30_date], [c31_datetime2], [c32_datetimeoffset]) VALUES ((2147483647), (6), (13193), (499351064), (0), (0), (1), (0.1990), (null), (null), (0.9601), ('üo.hý~BZ~|.b+OäÐbäv©,~<ßAöªB*ßuC~oÐözB@ÐUö+Äz©BAu>£BßßÖ,Ã~BAýýüÜ'), ('r:î.ý*_//uÜ@,ä~åãoCzÜ/+vÐý>ÖUý~+Aoî~zb£Üv+*oîbÄO.Ch¢rüÐÜB£ÃýäÜO:£/>rüý|Ö@*b.bO+©ZZ+Cäühb:C>Ã.£î>Oî_>ª@r.~,BåðzÄîvaÄ|/b~Ãv/Öä@ä+å:|uO©åbÜBö|<ävCh/ª/,ð+bhzäuoÖ+îÄau:+B@u,üö<>>Cßýz¢:ð:üöB@båß©îaÐbã:£ãÖbî+A/ýå~©rAuähðývbv+äa£/ÐbðÖ:£©Oh||_|.|örð/©Ü:Aåßb~.+>å'), ('z*bhha|äbZ~,/ä>*hðªü£ZAAö,bUß©©_v£Bräbß.Cð+Öz/£Äða+ü/zvAzbOÖözA/¢ACßÜv<bhrãÐðvÐ@Ö+zßzÄBö+bîßåA:ßåöCvZãö£|Zãu<|BAz©aªýuBðöo:Ä,£oBÜaäýo<uî/ÃUaåå>ßüä@raäã©ãr*h.uvîCÐ::ÄCåä:COZýBAbÃ£Ö@höbðOOhöhB>O/*îh/ABãý£Üî©_©ÖOv:Ü>ÖÜo©_v@üöÐ+£aå£Oî¢£_î_>OOU©_.Üä£ÐÃ©~Ð<åÜªäbð.v©£A|v©ßUªuvý>Z¢ý~oäÐîzrb</ÐåðA>~ßðªa>ã>Oã<B:ZÖýªo¢ZåbÃ+*/üBãöðz|ÐÃO~Äª>ÖÃ©î:uÖÜÄ:CÐZÖª@B|U~_Ð£Uîh_Äª|/î|h_r.ÜðC©O,.ßrAßhOba<AöaA.ýZÃbooAuOor.C*£Cî*+U:ÄCÐ~/<./@O@oýß¢:¢Ar,~aåªÐbÖ,+h¢£ßüZoü<ÖÃ¢ß©üÄUC_aÃvzhääÃUCã*ÄAb,~ä+a><<oZ_ÜÜ+îî>ör+©äZ@öB¢ðã¢.ü~ÐZ.ãåBb|rCA¢BB**u+.>©._v+/ðå_¢_î|Obb@bªãoß<ÖvåãaZª¢va<ðzÜC<:OOA~~CbðbvãhÖ|*äößý<ªð~öavîbvÐZ,ªo©ýÜîohßðöaÖ<@:hb,ýCýävªB|zuý©zü<_ªå+bA¢ä©©ü|O_Ö:ðr+zU¢Zß,oßz*¢ãuvª//îða+__ãüU/ÐC¢aUß/vCöÐßoa¢r@ZruU<ý:Ð~>¢ö/Ðð|ö/ÃÃz,Aßý><ÃbÜýÐ*a_ÜAOvhov©zß*ÄußUr~ö@Oðo:Uý_Ü©ß>åª.rh_Öo_@AÄÄý£Üuäã|.ßßãª¢ªöZ¢.¢äãðÖzA©Ð:/.ýhu::ªrZîßvbBÜ|ª_ðßåðC/äåbî<rrBBý,ãÃ,oh*bz>ßªÖßýÖ/hß£|<äÐ,~,ÄÖbÄÐoBÄöär+ß>ß¢£¢UCUðÄÜbZ</ðüüa~Ö¢zuÜb/©CAårÜaoÖäuCC:hßbz£:Ä©ßüîrÜbä<,ä_ã<håß|ZZZÃbÖãîCªãvðh£Ðýzbýaýüzv>+~výaîuÖ+h.ýÐubz£:ar:ÐîC>ßßã|zzý.~,v¢hªðAoÜ>za~ãðÃ*_ZbA£©o@OB.rAã/~oäßÃ@ãbovö/ß>uüðäüßÄ*_Öª:£/ã£üÐ©î//ýCýßÃrðÖÃU+aöC>*bAÄU£CO'), (N''), (N' '), (N'ÜîbßýßähC>/Üb+b¢<Äo¢©¢OOÜäöÖövü.:<<<ühhÃ>ððuãåbßoUü_+Ã_hÄåbCäBrä_a*ªî¢©*~<hÖÄü*îßCªÄZ/bÖðzÖbBaã,ÐbÃß_<ð_ª£üå|ß/Ö_hzÖ@>|Bü~©~å~U~£ÜA£v@++ObB©:orhîa_öÃbäu_~äüß+©a..uÜÖß<AÜü|:,å©ß,_ÃäbZå|îßCÃ£.~ß.Bvbö/.C_@oÐ@.ã£ðb~UÃßüB@©åð©ß,,.ÐbZªªãüC~*|hoA>ý<Ö~ßzhªhO_v_<:ÖhÜzÜ+_b*U¢hC</ü+¢,öOAß~Bª:o.b~/©C~/+ãvh://*OOAhÄro£o_UÐzruOî,£U©r<ýåaª_+U@Üª>,ð~z:OîðUCääBoªbubÜO©üÄAUÄ¢£vîA>Cãßz¢ªð:*Ðö+hAð:ðZA:ý/ß/u_bÐ~v©+B+ÖU*Z:£h*ãðA*îußoÄýåª>ÃA.BB@OäbbªUÜZ©C£ã,Z©ªå,å£ãö/|Ã<@äbröO+Cö<Üä/BCöCAbh>ZãBhau:ÃU/@.CÖîU<o:<v+rãAüÜ@îß,>_ýuAöböüð:ÃAU<Z+uÄ©ä¢©uð¢vCA:öÜ:Aaäh@äZ,u>|ã©o~~©/vrh@åäÖZ.Ãüî_üÃUýUªoã+.v£hC.U<Ã_,a©|Z@Ca,r:ª/*ü~h/o*ü@~ðßååÖÖ+böa/@@zCö>CªÜ£îßãZO_UãZ*~ä/ÖÖ.¢,UAo@ªaã:vA<Ãu,ÖöaaAª~rCu£©ä£b~,_äÐ*Ð<.Oã:Ðvu+¢COC<ÐÐ,AßbîöåöoO+Ä£ðã>ba>¢ãrhãöîäA_©_¢ã©oßðvã_@u©v,|~/Ü£hBýBîOåüBabã/@©ÖäýÜa|ÜOüBª_å<,*:Uå@/¢ÐÖýöüoã:©ßoª¢Äª£ÜB,ðAaZ£a>ÐÃ¢ª_ýUhCußb~ª+@/ß+vä©Ðß<ßC@ªÐ:*ð>:oãbvO+>'), ('Ä@ß|_~@ZÐ£böãCÖäÐ_,Ü:hBhrÃ+_Oî+b+ðÜv*öboöbrä.raö@z¢örª/~/©+ärýCãvüªî+>_ß>rã.öuÖý©/aäv.äÖ@Oã©ªrüöð+~/¢C~AÜã<aÜa£Oî+OßüU|åBã,O£ärvUå<~©*,|ãýb:öäü@|£ÜUAoýÃßÃzßÐ..ð£OÖî|b@~ü¢üýoCðUäå¢ýBüUbåßÜüaªã/ªoßÖCbö<COv/ra@ÄÜbðäÜ.ßÃö©AÖz||,ß_,azOhßðoäîÄãäåvbrö©ü£O*Cß:Aro~£å£Ã¢@.~ðCö¢|ðýCßh<U*ªÄßår©O,ÃÜÄývvßrOUÜÃ£a|AA¢/h©£îÐðUAoßß.ärOu+u£Z£oð¢åÄ~Ä~oÜä~uAÄbäýbaU©,,ãð<:ßOß.*ßýÖaaA+|:ýböã¢O<O>u~v.>|ubÜBUßüü<U:AüAäüÄ<ß©*/aZvüªÄ~ßb.>rßüUUZ£Ab>~Oh@h¢våßªßrßA<ãZr'), (N'Ob|ÜUîÄ:oo£Uö:CC:<bzöUßÃýýðß.a:ÖÄä.hªÐîÐ~/£:oüäª+ÄãÖObÃðUäßvüðAoÄå|bbý@Ð|+Ð_ª+@aC*:ÄOÄ~¢o<îÐö£Ö©,>Ðv.Ü|+OCOß*o*å+Üh£>*üazðð~bßÜð¢boB£uª*ßb@b_ÜZaha,O>BoA@Ðü>äüv.+UOUýuuhoßä.ãð<|£ð:b.ª:_îCªðUC>ßßBÄv~AaîÜUaÃUÜrªU/äö@rýÜß©*å£zåbzA¢Örrüªî_Zo~Övhðý©ãU|z~Bö<îÃhv<+ü,rh:,b<vAýðßOb,rö££AßÜÐA£vÐbîÜUßßª+ba<ª¢,ý@z©vÖ,_>O£ßzO¢>BßCz.+vÐBaªÄzO>AA©ßU.,+@+_.ª¢a>.Ð~î©>zü¢Uª|vo©uzÃªß©.+bÄ:¢üäü©BB_OýÐvß|ÄÃoaßßB*ÃzBîArãa+@rrä*B©~><ÐýÃ>AäAbüu+vå*vZ|äÜbåÃO¢ub¢Ã,~>:z+,+ýßbAaCäÜauzåªb|ßßåýßZîBãä>.,.ZzÐZUC.oü<bßrÄrðÃüö,©Ð>|oÐßªîOU~rðbÐªrbßä:£/öýbOîðo@<ýÐ|ßOh_zßvÜr<ªv£<z<åh_ubb¢O£¢ÃýAÖCrO@ð/b|>AOår<,ß|:¢Ã¢z<@<Ð~üîÄhvÖ<+.bC_å.*©>AråhßßbU,*CßÖÃAOäB©ßîîhÐBüÖ:îÐZZ©ý<vä/ob£åî.+|©.b+AZÃä~hbhAãbª*£AßhÖrÜz/ö~haýÐ,_z++|Äaªß.*rÐ,zab_v,_/îOzAª*CÄ|h+Ü@_ÐUßðAu**å|ªb£~B<Ð+ãC.oA@C©>BäOäüåÜvßÖ**zB|ZÜ:|Ä_¢b.ýð,h|rvß,Ðu>ÃÄ£oäZZÐ,a_aãåvb~üü>ÜÜäA>£Ð~<|öªäbªbß,äUz+Aü,,ZÃöru/ÜÖÐîäª©r|ÄvãböoÜvrvOBÄO/Öå©C*A£vz_ãC*ÄruÐ£ÃªBOî:r_/ÐðAzb~zhüð|ä@bý@_Cb/|ab<<a~*|ürîß¢'), (0x2B9CF79ACA037A920A611D2D084EC96716C38BAB3A2561E2F72EBD410B4E878AD0AF8839949C3CFC8AADD5776C4A0E4093BBFEAD67EC17053B14A29B463886BCD9935C59C68FD279878E218D50E976AD1F7CAD4F4FDA22E49782FD7FFAA0B65226189ED8EDB57A03E98525D8CB8AB5529DF47CC98A366DFF7078C14392527FE7D8CE66374A9EBF6A01A966CAB44A), (null), (0xABE20623ECC7F0AB9F6875F3377196053EFFFC237D07FF3412C7E0CFA34A255FB475E77079C4EB07768BAB354A76CA38D0C4764A1CE94FA3E2D11124E620085442163EC28A268C7A14F5ADF25CC9E99BFDA8FFDED4DA42CB25448DA7B2C620568356F3343708AEAA38FB36273B5D4619E776273E20297F67D3739C4698E4C74DD4576E06D6B563F5FA54A6D8A31B8FC7FD73BF3B9FE23559D78B60C46B68115334E953E4EE404331123144CB83A6C4B7E3D44824258BADF222152F4A559E89914352A4BAC8ABD9BD308D286E9D69D530C7A8E1961FEC2EA83C60C2D911F8DC74A7B288300109EF37FB8AF497633F54B93FFE83ACBDF3715624FC7D67E9FFAC37B1B4C6EBE0A52EBDB9A99A95F89745FD0C0999127530B6B440320C048C874DFCDEBDE5AE7868D89ACFC6F21358DC5D8DB7805BE06699C280FD12AAAD982A30325C9EB73BB19684A330FF42AF3EA35B63D47E4C0C2C6D9544FA65B066BE2A01A595C0B7BA2522DB21FBB7B0675713425AB647D819DD2405EE0872404CE61EE505FD9C197EECA13EB2A32B1074BD2EEDE72355AF7752E7C870BAC8B1B2E18F3ADCD2AA69BAF9C36E15082676B1149131DA855828906F6EB315D4A9C323EF8CD013F789D2EB13643FB0619E), (0x00), (null), ('5312-04-01 17:27:17.661'), ('2062-12-14 20:38:00'), (null), ('04:51:26.7756160'), ('1698-07-24'), ('8837-08-20 08:14:26.9398584'), (null))";
            break;
        default:
            break;
    }
    return $query;
}

function RunTest()
{
    startTest("sqlsrv_fetch_object_class");
    echo "\nTest begins...\n";
    try {
        FetchObject_ClassArgs();
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    endTest("sqlsrv_fetch_object_class");
}

RunTest();

?>
--EXPECT--
﻿
Test begins...
Constructor called with 3 arguments
Comparing data in row 1
Constructor called with 3 arguments
Comparing data in row 2
Constructor called with 2 arguments
Comparing data in row 1
Constructor called with 2 arguments
Comparing data in row 2

Done
Test "sqlsrv_fetch_object_class" completed successfully.
