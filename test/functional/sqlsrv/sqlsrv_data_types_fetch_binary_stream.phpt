--TEST--
Test insert various data types and fetch as strings
--FILE--
﻿<?php
require_once('MsCommon.inc');
require_once('tools.inc');

function FetchAsStream_Binary()
{
    set_time_limit(0);
    sqlsrv_configure('WarningsReturnAsErrors', 1);

    // Connect
    $connectionInfo = array("CharacterSet"=>"UTF-8");
    $conn = connect($connectionInfo);
    if (!$conn) {
        fatalError("Could not connect.\n");
    }

    $tableName = GetTempTableName();

    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_char] char(512), [c3_varchar] varchar(512), [c4_varchar_max] varchar(max), [c5_nchar] nchar(512), [c6_nvarchar] nvarchar(512), [c7_nvarchar_max] nvarchar(max), [c8_text] text, [c9_ntext] ntext, [c10_binary] binary(512), [c11_varbinary] varbinary(512), [c12_varbinary_max] varbinary(max), [c13_image] image, [c14_timestamp] timestamp)");
    sqlsrv_free_stmt($stmt);

    $numRows = 0;
    $query = GetQuery($tableName, ++$numRows);
    $stmt = sqlsrv_query($conn, $query);
    sqlsrv_free_stmt($stmt);

    $query = GetQuery($tableName, ++$numRows);
    $stmt = sqlsrv_query($conn, $query);
    sqlsrv_free_stmt($stmt);

    $sql = "SELECT * FROM $tableName ORDER BY c14_timestamp";
    $stmt = sqlsrv_query($conn, $sql);
    $metadata = sqlsrv_field_metadata($stmt);
    $numFields = count($metadata);

    $stmt2 = sqlsrv_query($conn, $sql);
    $i = 0;
    while ($result = sqlsrv_fetch($stmt)) {
        echo "Comparing data in row " . ++$i . "\n";
        $dataArray = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_NUMERIC);
        for ($j = 1; $j < $numFields - 1; $j++) { // skip the first and timestamp columns
            if ($j < 9) {   // character fields
                $value = sqlsrv_get_field($stmt, $j, SQLSRV_PHPTYPE_STRING('UTF-8'));
                CompareValues($value, $dataArray[$j]);
            } else {  // binary fields
                $stream = sqlsrv_get_field($stmt, $j, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
                CompareBinaryData($stream, $dataArray[$j]);
            }
        }
    }
    $noActualRows = $i;

    sqlsrv_free_stmt($stmt);
    sqlsrv_free_stmt($stmt2);
    sqlsrv_close($conn);
}

function CompareValues($actual, $expected)
{
    return (strncasecmp($actual, $expected, strlen($expected)) === 0);
}

function GetQuery($tableName, $index)
{
    $query = "";
    switch ($index) {
        case 1:
            $query = "INSERT INTO $tableName ([c1_int], [c2_char], [c3_varchar], [c4_varchar_max], [c5_nchar], [c6_nvarchar], [c7_nvarchar_max], [c8_text], [c9_ntext], [c10_binary], [c11_varbinary], [c12_varbinary_max], [c13_image]) VALUES ((1), ('üb_rÃß|AZ_Uß.zðýªu+bz©£+_ü_©+o<öoý@îÐbÄÖðîä.ª>ärBãý>brCZ:O©ÄvåÃCbZ¢/ßAU__ªh.ßuÖAB*|_Oýîüär:|,ý>AuÐ*Zä:.b~:ö:bÐ@r:¢¢özhöb.©|ªªÄh|CCobî:O©ö>üÐA<îhÄuBÖ+_ÄBÜUîråß>åZ.zö|/zý:öaãOubðÜ/î+ª.O~.bZ©|ýßhab/¢hö~vðCz/_åð|Oü/:îCvîrÖ|>h£'), (' '), ('ßBß/ßãã¢:>bÜA@hÖ<~|*uåß_/.,BhvÃãUÃbãÖÜO~ªZÃßß/ÄA*,Oåîß£öZva.:Ö<oßU@+<zýÄ.ýÐöraÄýÃ+ÖÖBßð:bC_>ÜÜhäß@ý|î+~A.<<ðBÄÐv|aÄBaÐbý*bz*Ü>ö¢ãåCÃahb*ÜÐ*¢*ý++.vöÖbAãB:ýuãbUUð_äBr~bU.ð+zZ,:©*Ðöö@_O|ßAauaðUZ_</B<îhÜ*a|ãÄÐ|Ða*åBBzÖ,*zZO|ßu>©bÜßö*O:ªOª>ý+ãb<ZuÃAoruÐbÜo¢ör_Übo¢öÖÜÃh:>îÐU,ý<rÄvª|:@ä|Bî~Aã£ýbÄðOrCÐ@__ª©Z@<©ßÃ.Uððu¢©Ö.£åBCªäð©|:ßvbüÜbaãä~A¢uß+/.bh>z_:ÐB¢//ýß,äðrý/zvoårÜßO+A©zÜo<å_bhUß+åh¢ªäbOOÄAßðCCåUv|Ðv+büC©ß*>*äbÖ¢ö,aU¢ðÖýo*£_ãb+ývßª_|aßO@öAÄÄäãöAzoÐåa©¢üvOÐ*~ßÜý@bb>rbö|ßÐÃîbZz>Ã>@î¢BUhü~vßBäöCUÐOßÜ<ãUrobvüÐðüåu©bzä+.ßð@£ßh<üß~£zC@Ðr©vÄ<Aüou£ZðäOäß.,ÜýãöO<bÃB>.Bªä,@:/bhî+ãÜZý>u¢ÜðÜuö*@åU>ß©Üö>ÖCb:_*Ö:ÖU>.hb©Oüßbz~£å©_ä£OðU>C.+aoöbÄvA<aü<ð+ßªZãäÄ£ðBU,ð©Ä|@@Ü+ýßb>uîBßvB£îOª>ã~£.¢/Ü/îî~hA~<ö>OBð~ÖCbðÖî+ß<+OzoåüåChöu+o~©ÜbußohÄB©ðB<~<å@.¢/|,ßåÃ>b,AoÄr~_.oÃ©|©*£B£uÜ@z£v/ßÖÐaZöO_zBÖ©ðüÐ@ðOzrääöBîvO£îýÖÖÜÐå:ä@Br>Cu~ªýoÃ@v~hzb~üß£CAÜ|£**aåOBv£/bCÄÐ¢,>ðB>C£/¢/Äoî©u.b/ö>îU~Bü~U¢obr+ã*/hOðv/ýîÄ~OAOª/aýÄÜUÐhîãCðåav|u+*~uBuoh@>ý_aðö.£rhb@~~ªZu*ß+|ý£o+ßÃ¢+zÜã|UÖÖ¢îðO_ußC>ÐßÜüöäå+ßü@*+£BvÜvå,Uözßä©z/¢ãzO_Ã£Að£/<î<ÜZU.UvßîÖüîÖuÜ<öBZ_.Äb@r_bah+.Zðª>h©£rhÜÐCÃbãh_C>o£býý@~Að*£*îB.ÃÄå~Uªö©å_ÄöhýäZhB£bÄÄa*Ã|ßÃ*îb*~o.ÜÖüåîrýr:v|:bbA@uUaÃýÄ_åÃÜvzü<,+ü~b|ouÖåÄ@|bCCÖ¢Z,ßb|>/bubOCðÃUüîO<>ý>+ý@hü<h<Ü~a£a~£zß>oa*bb£öAv~oö£v£ßAã~/@ööÖz¢Bv©©z~,ýzýhvöärör|ZßöÖaO_ßÖ,üub~Ö:_©,ªUª|£ü£©ÖåÄr~Ð,<@©+v<C@ãýaBð~_@z|~¢ZÄUZÜ+_ßöAO.>ÃCUÜO.î>ÖÖ~¢äÐBu,,©aCBÖU,~îö*|ßß¢ªu<vÖho©C|öÃÃî£:||©_.,ª+a.ª>öhOüÖýv>¢ü£u>*ý|ß|ÃÃU@ßahZuu£Cv+_©Ðü+ýðÃ£hßªð/Ab,b|:Äðu:+Oã<ÜAä@ÃvÃã¢aOß¢aACÃObÄUoäª|ß©¢/vªoo<îªZZî©ÐO:+äÐßÃîCÖOCCr¢OUöå¢b/*/a£oC/ã,Z/übÄ>Ðã'), (null), (N'Z@/bAUZ/A+AA©ÜBöãb©~<v+åÄohîÃ@Cå£+CãýüªðãaßAv,Zzª|îÖ*.vAh|*åB,Ð@uh,z£vüä>ÐÐ:<zªzäãzh¢,_uBBÃ/¢Ðã:vZßCýý©*ªAv¢©ß|ßöaavbªåä¢hr|ª_UÖîZOo@<*äoü_ªCð~Z_ä>ªÄ|,Ã/ö@ãßCÐbß@Ä,£.z<ÃoBBbZÖÄ~ðaÐb£b*î@Ö|ü>bbU<ßªrO©C|að_<Z¢CrßUäBaAh:,>C©Ö@rbbäª>h>.våObÄ@å¢î¢*ouröu:©oãÄ_<ß>Ð:./a£bzäªrãý:ð>äub.+üåC¢oßý©ÄÖ/,|ýb<råüvã,¢Ð.<uu*ãZ/,CÖ~Zßäãb£oäß:/:äA©+bh@ªU,uÃªÖ*ä:ýa/U<O.ahrOuÜöüAÜ,zOö*U¢o*î~ou*zZ.ß/ýr,äv*öö*,>:U£ß,A©@£Ößbhvßäîäåð,h_b~ý:ðª.zZßä<z@/üZ£åh©OCU~:>bb::BZÐªu.~ÖîU:_ZZß>*>åªZ>ýäå<Öå<C+uã*ÖoªðO~Äåüîu£ãößüh~Oäo¢zäÐÜ©ÄvßîrÃö'), (null), (null), (N' '), (0x1A17A8AE2F75EF49041BB52C2B8410480F3AB04E569C7253A385D9AEFFA6B2CD7DF47772A908D645187B9C5BEAA4AA9616CD1CDD6D47957DA5540BA99B41495E69E8AE370C9E9F07636B36DAFF1669FEDB43973189D55F365FF9CC55FC2DA9D7313EF201F6738A782E14790211BB7D8EEA0CCE58A918D4938977D712D14A0A36C15EA8C123680867B81F03755B5C25A37C3D6AEF5F502E19695E787E9B77A7E3094340B212BC6B8103669F89155F372C52349693DF1BD771308F57C5EF8ACB40781F6B181C7290BFD2E8EBF8B0A4030A264C2B67E11303EE6BCF828364A692799A5F11F5F31F10E0A3D6C0F62FDEC08C8B4D9EA35E645F641B7DAC4CF00D5A2C56725D51920E17073C4BC40A520DBF8D0FF5F71707EE1A36FFACDA7672C3EF957B94A0BD806EF97D36966713A054120EBE1F3DFA15432467572751E634A095785B2E8EBE6D020CA0FC7AE171870796AB9ACBB85286F57F386F3865591C12499500EFB771C553A1BC129CFE9B61B3E04F91A64C68E842F166AE564E349CB384E9314C70985024E5462AFC40EAF6E4D6BC5815C775C6E0ACBDFD50C36F046C1F5E18156A154247FF6CC1053EEC02076FC97F92DC86EC0E22509FB29B00607541BDDCE1D1), (0x7A10165BB4C0A2C0BBF5E0F9F07290987209F219768FACFF0B4CC4800F20358D4FBE18CC70905A61AEDEF7A51F6685CF2D446FC8EF604BEA63164F68FC21B09ABF52AA325AC427E1AF906B979706DFA5C7F452CBB16D9EEF722D685941F2658C2E5CE5CCBC5275691C8D185FF5B4535C37AAA008CEE5E952B4BABF2C5BCDA0BD2BDE683B2113E8747FE9AF23553EFABEDA0B2296515DBE), (0x93170192480D4EEC5B34174B183B23C12C89E536C6FD75C0005250AB0A6BCD2020C88DFBE6E136DB6036F90AE373C2CFFFD424D4D812AC96A24192F6BCA3E2EF66F72B4FAE27DB4A5209D58F036ADD8D9081AC3546F9470D1BF659A54F3F1EBD5309A4EB487220D7F9DA81CF3D1C93376304663BF7ADA86CAF1D34B8CE35FB13EA183CF9C6F1441D61D43B6C7878D0FF6322414AD9E3C6A560383C2A3E6809C3DE6EE425E0997072C048DA22713CF4374D3454ACF4B139065196988EB51ADFF8CB788E0A93AA1ECAB1CF8A9009A333244304DC09135DCF2D34DDD34900E2BDA569E157F9ADE9A3A3158AFA2526A8FDAE62744AB03DD5E56EA861D5B6D7D5C9CE12E427284C3A76596039F38862A9068CE025E78B980C6893FBE8504C14EDCB40C02C134280D46128FDF05003E25C0CFF5A49D6B1ED3D36057B942C63E92945736DB2C73596C40B0BC4BF8EA7BC64B09FDC51F2CB17195383F60DC6ECA8FD3FD3ABDC952F94D668963DCAED2EAF8E4917E38D879737795B545F443F89FD1F63C680BAFCDFCF810A05E95B8E4C2C24D367C3AF081B76A79B318141D04F96DF981D6727AEF4916A48628281ADDBEB0E273A70D8C54E164850D81ECB5D8DB0BB6F0AC370E50575DFD55967A698F4F9C045721995DEBE194A071FD6D6D5C60CBAB1C712A3187B2822C491026BE770AF5BDE5E85EB731BDCB0F07968C62E9F090F4DACF4D1E256A7CA49F7D04DBCAE696707BF77640214796EA61B29F1262F42587F21D3A7BDD7888B7BAA17C4A8FF44215D256B2A893CF2C655EE681A308B8E702ABC5C990281B92026FBF857FCB4B5B6C7089400C427CF423F1D51CF6F7E4AF02B9799FCA2AB1EDB6C3AF38D449AC9A138B14337E78DAC02ED0A69F53FF9B0B8A1083C651AC9142BE9C8948924320D9443A096AE3B0308ADFB970F9831299B728A4AEC3C6E64866FAD175E2EE922DEAB0CC5B18F53028DADFF8E6983B98BEAB02D220ED955C135DF2A270113A4D9B4C8B8648B7B781C1F77CD3E4CDFFB02B6213CAE01C713F5453B9FD2653C0169F925C9A0DB54F07F480102871C278455E41760EFE706D7FDA4B31F4FEBA5A6D376534517065A375120CD55F0C49CE92DB739EE4DCB1182F25DDBFC1845A093925A2F10B8E26D4F89E01FBF19A0D72917C9E198A1B7690854D042C93A6E1F1B8039B396957C204B49EA645DBB62694176A2D44E5407E1EAF116BBE89F94152BD1043EEFED3093ABA9A64EB7430B5CF26701528789C0B77847D8A58EB047BBB5D5388CB7237954361F38A4BDB93A97E5C9C9818E419D92004CB6A8149B14EB61FA98E21A8EA5CEEDAD1C68511C09BF1D30BFB4F6B62D6437A67C6A7CD14FAB0A4D42B320488D6713FA93E1353540E443079AD3DB5FF4ABCD34AA64023B521C6D615E13D9469EC4A358A0FC224C3F0265982775466BDFBA4896F5FBAE1954D8988773C3D72D0AFC68C63072BF088980F5A80E6A13ECD5F191A3DB3250A3268A03C8539AC87B2B388D2B0C703B5D0B401D64F6061AEAC4BBB172223351D8ABC5AFA67EF6A4EEC88A7CD5ECF8E055C634989B24EBAD207F1903A7E94648F7D6B47F36C094EE149B2E98F6D8F4FF3B1E60AD7B), (0xA679F65718F8D2E39A5B42C159899038E98BA7DD2A8CAA8FC59F0775F7204C66E8A75131DBE4A8F3CEB06D9B9532A6B28E27C4F015DCCD19CF669178F05CEB7A0EDBD9B1385F5D04E2B30E791F0FF992F955E7CF5B5B510BD938627453300271C5F38CBA59F4792ECABF0E61CE54FABF6DFE7E9B751C1EB38ACDD700FDE8B1B1B19CB42B823F3904DBB9049B4DEF3D853DA20754B166FCA8D4D92F8B1D7930E20E5CAF31A0CE3C2DC479C0BC1883659B9052C25BE05D004BA00C57A90FAEE76188228DF13F0BE56F65DA8F37A6408F507394FACAB7D501B15B64919476931D16925E33F6F5735B846D52F506CDC8A45F7C06357BB41CAAB220DC96DA7A50F9D1BC79A5470C995B0039755D64DFFAE392BF31186F5DA26275B381D519DD800A1C59234A54235D53E2AB35423EBF46D225F9031A49388A741A1630938BC34122B86B410B))";
            break;
        case 2:
            $query = "INSERT INTO $tableName ([c1_int], [c2_char], [c3_varchar], [c4_varchar_max], [c5_nchar], [c6_nvarchar], [c7_nvarchar_max], [c8_text], [c9_ntext], [c10_binary], [c11_varbinary], [c12_varbinary_max], [c13_image]) VALUES ((1395754036), (null), ('ßßÄý@åo£UÄBüîvö~£:î>:Ð©ÖîzU@av¢vÐbC+rýU:_îbUo.ä+|bBCBªÜbhüBr£££üBßußoª,£r<ü_©.~>:åüöo>OhzbruîAßåvBo*OArüÃ<ð££u,Ð.ª'), ('ÃÄßª£B@B,oÖo©Bv~UuZ/©ýUÃ~|@b©>ãZvAO|v~ÄB_C~_ÐÖü_üß>oz+BÃ<haBª//î*:î¢îÐz©_rå|/Äüã~üCäý©ýðö>Bh|¢@BÃÃrvã+£U>ß¢B<_vÖoO@¢z|î©:ýÃ.¢OCîÃßåÖ@Ãýu*å,obå:.u+ü,BåÜCÜ_ãä©u¢ZÄÐ/Ã©_u>OZZ:OÐ¢~_ZOvrÐßßaÖüüäh|BUb<*<ÜÖÄh>Oß~*öAðUz,b+Büð¢©î|U£bÐOrBvZBÖ|<CÐ|ýrÃßðbªßObåý>.ãBãhåähB.CãÖ£o|ß~u+/ð.ýB£rAu_hzCßåv©Ä*Uå/îß|üO.:U+<©£b>:CÐ@Ãßî|UßÐüÜåîCUý<aA~/h<ßªüO©©>Ð,ß_ýã~b@+~Öur£|*Uu©ob<åÃ~bårð,ªhUU,b,C/..>Ãa©ðUob¢ð/ýz©ðöã/ÜÃä~üü¢ýüv<ühßUÜ~ßäa©ßÐoZ:ohðª:C_Ä£Ð_ðÄAbðZå~©ß¢Ð|bhbãÖüüÄv_ß<b,ß*:/O_u£bãüÐ@ãª£©|.,@zbÄö©Öã£Or~äîbur+/Oz:bUäA:CC¢ävbÃå@ZÖ<@Ü.ýî¢,ä¢Ü+:ZCubbUßãðr£ÐAzö_OA|ð:åð/ð:o¢>oã*ÖO|hã£ßU>ð|zh©ü~ÃäÃ~r/ý.rßC¢bO+Ü+ZüäÖßÃ_uZ:_*¢vv<~ö¢<UhzOoöA_OoZ~ÜöãÃCãåoo+îý<b~@äo@UÐZBo©Ãªã£aAChü|©:~+Öb:zîu*Äb+@©a©bã>ãÃA:ub_hzÜä@@ðbZßÖOvýrözü@<C*A<bCbãÜ_ð_aðO~:>r~::,uðä~@£ÐÄ<Ä~.ßZzÜ./BzÖåuO+ß¢>ãÖöAÃüß|r_~OAß:ß:_,ZCÄßh+C¢*ÃÃuÄaUäÄÜ_Äã>Ü|ääîZð/.Öß/*+hößÃ~*<ä_zåoÜöÐbîß|ß+höv:ãÃîZð.vÃåÐÜöäh>AðA|üÐ@ýÄ+äaå_aýa_uÐ£|~ªîª>îuüBAÃbÜ/C,zÄzÄrðb..oäÐÖ/£.ã<+/©+UªÜÐUßzßÖªå£u@/ö+£@>rzb>uÃ,ZrZbÄÐö:Ðö<©vB~.väBrCýîCÄîooð|v@~/Aª<_oðZbÃýåüU.¢ðÜ¢C<vzzO+ß|Ü_o©üBãbýöÐÄOðbZîUÖ£Ö_aÜ©,BoÄZvo++@__*ðå/+£ýAðö|rA.ö¢oßU,Öð*Oãz*A/z'), (N'üöß~¢hoðzÃääÄArZuBý©@~uZÜ@ßÜu>ß|A+vO/äüCz>bbb<>ßCbß*zUz,Ä@ßCz.Ããåzã+ÜAå+hä*A@Ouzaa£OÃ<uÜAå>îbäßðßUOÖ¢hABO:r<ðZä_Z<BZÖ@h/bb*å_üZZb.uvý<Äåý|auvÜäÐAüªªÖ@OZBÜ©|ßo|roî.@bª_ßÜrzO@ðhzrªä~+Ã_|uöOußhbßaßb+++©//ßã|OýÜo¢++uzüã>,ãvh/ZA¢h@oÄäßöãð/h~ãü,uoa¢:ßä@bö~ü@B_ahÖu.©ßbzß.@ZåßU£.<Aü.Ã~ãZaCÜZß:Ö./aß¢~Ä.BÄßbZ|¢uÃð~bb|ª:O.C©ÄZaýüÖî<ß:AãÜ¢åÄå__Ð.åb+U,Äãß|£u:AÄßÃßB|©h/UîÐZ:©î~©ä.vßu©Ð~Ab©Ußß.ÐðBAb/ýbU<ß:_Bvoðýý,AÜî|ab@@ª>O<Bz~_|ä>uä*a*.rr>ÜîÐUß/+öA|aýð><ÜZ++r_Öªb>Ö@ÃÖ|hßåAÐÄÖö£Ãö¢@aä@Z+åUbbäýöAZÃÖbîÃr<.rBåÜÜA*ÄÃUðU~rý<h'), (N'>BöîbÃ.bOªÜv/A¢£..ªCrÄ*OÐAßÄ<ÖªÜåÃýZUrßb+ühCðü@rö~~b_ÃzÜÜÜobîÃª:aý.ÐA*,Oª_Üu~rvÜ_~Üîo|uO,u@>bÜo©,ÄCð~ÜOªO,:aðbr~@.@zðÃv:Ür*ßãåhã+Bääb+OÖ,oÄÜBuvzAðý©UäÖÃ:©h+/.ÜZU¢£ãu*>orÖðÜü>+|öAÖîî>~@*o./rã*ý,/_<~:BÃ:ã:@ðýÄüZª/,£ä.ÜAh>böbö,/ð,|öåü@öb<bAb~+::Cª/ö+_AÜ::öAÖ,åbbý¢oa/£ð:~.zÖü|h¢>ü+ü<~ä_O:@|îzBOöý~ÖZ:Ãö>>ÜCÄA<îß/v/_C¢/Ö¢ßüh+_vzhßýü,zbv.<ýßÐãoý¢Ð>bÃ©ß,oýÃ¢ÐßCü@ß|u<ð/CuhC©¢¢aÜ©ý@£br£<ý¢ß.£/_~*avövð@ÃZ,ß__oÖ|üîüÖZ>ðZobUäCA<ß¢|*¢*b>Ð<ã+©,CöãäU/Ð<ßväBüÜOÃ£|UîAðýãhuî.uv<hUðbh/ýåªÐ*ßv>@+ZZAßvBÜ+rüýÐa_*u_ßÄäbÐbv©Ã©oÃBÄ+'), (N'ªBO:Ob>|ãO/väb.CZ<Ü£Z~UÜoª/ª©BU£BoöuAb¢©,@åää,ß~Ü~ßU~ü|aä/Zª¢ååÖðÜoÄCObuãBvßÃ,ðÜßäßbZabCªð,¢ÜAb>¢höbuB<ª@~.<ÄÜ*<>@ßÜbß:ðÄACOß<:ßbßo_:ã|Ã,u~Äªª/ßªîCUuv/ãÄa,Oývßbh¢/î¢ßa£üC,ãuÐ*C¢Zªî*O~Ü,*ã:<+ÃC:_~ýO@rßå<ZCîªªü©U~ÄÃîÄöA£_vü~o¢:v,Ão+öBBßaåî:åÜÐäÜuÜü:|ß¢ö_ýðßzZ©©¢rðßzz>¢åîßbªÖÖ|Cßöä<ããä_/.BoýÃ~uÜU~ßA<îv+|£ý~+ßu,Ã@*ßrß+bZåð,:årUßß>OÖb*b~CvOäöb¢åßZOU/~<rÖÖ~ýåîÃ:ÖÖ|Zb¢ªOZub@OO*ßZ@©~Aý,uAüý*ÖbÄuªÖCr_zßoÃýör>öA~/ÜÃ+uB/Ü+zvb£v:u_äð/bhZýÃ>åAbOÐbö.ðü,*ý>**ãrbÖ@/|ÄÃ£aª@Z<ÄÃÐr,ÄOªOCU+<äOå:BÐZBðöü<<*@Bb+î£@©©Uvö¢©¢å¢vbBä>Ü~>Üý,ÜÐÖÖð,Ã>CÜ*ÐöO_¢ooB£<bzÄ.+<a*>äC£Cåð>_AUüCÖãö*h©öh.*Ã*ðÐ.b*|ð©o¢ðubãZªC£Cä©+Z>ÜbußbOB_:zro@:r>*zühðÐvz++AÜrã©~:ðZv*Cªü_,ÜÖ£hBÃ:£_*,o|Aßî:Ähzz¢££Ð~hðouÐýª_ö@©uU+*~ZzOZ,U>/©åßrbovaOb©_+Zîu+ªß/a.OBÄa@hO>Öv~rb_/<uýÃuCßªBU£rß£Ðî£ß>ßã/BöhßÄZ,üou,ßî,ßÐßZ,@ªÜåð+äA,ð.~Ðªbhb|//>rhb/ßaÐ*Bðar.*ßzbr<ãvaAÜ©©uãuÖo,åîz<Ã<C*CÃvý.+*UUãý+ü~uOCzÖoßîC+Ü£*@A+¢ªbCÄªÖA+Z*üª.Öß/Z©C*,Äü|u|îuÜAßuår,vãä¢:bü<ããZÄ¢üuAãîaUîC~>Aäßªv@ÐÄr+_~/AÃU/uhÄöãvv|ýü*åö>hv,ª~Z,rzC~@U/£ªßuÖã©ýä|O~_Av_.ãUå,Zb:BübAßª+*Aã'), ('BCCßo/ß/aÖ*öÜÐÖv,b+_+hÜß£ovOä|Ähä,ZÄCbuåuabuÄbbå¢b£Ü|äßü**oýh¢~<brb./Ahzã<z+b|b|C,~ßî'), (N'a~z,ßîuÐª:¢O@a©:~ð*öB|:ðßUªäÖbÜ|C¢bßC©*.¢ÐÄî:,~|<öýä~ý><C.ª|Z+ü|©bbåüãzðªð*+ã|<.Ãb.ßbðýhh~ÃÄbßBUÜhu.zÃª£¢bv©©h£~öOäUüo>ÖbðÖ,hªäßßbªÃzbBý<ÖOý£z<aöCÜåðäZª©>*,öu>Ar<ã~|öUäBåÜrý@BÖ:Ð/aßüåößýAãÖÄbUvoÜo>î:aäîßbbb¢h,~ðrß<rUªåß|ð/C,ã_ößÐðv|v/oðoßäÄ,å:oÜã:ßü_|ªBîB:ß/u|hß/<Z+ð,zvbßaBOAüüªýbhäO+Ã<,*ÄhÖªz|~:ýä.îb<Ã<Ü~aÄb<ßßu>aöUB¢î|b¢hzOÄ_¢~ýBOBýÃOÄ~C:Ãvuzö,ã|£råu@+Aä_hªä<B<>Ä¢h>zÖ_@@:åÜ,ã,ßåýü,CäÃÐüß£~ýUü~ßÖªzu*uåÃ+Ðb>U<ÃÜßý>CB+äð.@>Bü_v<Zý£Ub~_ÖbÄÖh¢UÐ+üßOv>:Ã,Ö.ãßÐÜÐã<~OUã:Är:Ðb.¢ÄZhßhhh*ýýãZU@©ÐÄAZýî©Ab<_@BýßÖ.vÖABÄA~£~><ª*ö~öb>ÐÄÖ/ÄbUb*¢£A+Ou:A>>|AÃ:©./r:Ð@ö~b>bb©äbýã¢Ãüa_Cv,îðB<>:AvbAZãß+ßÃbýäBOÜO:>Ð.a<©åZðbß~ãC~bbßªãÖhAvåZö|vOOCÖ©üÜAAß.hýîÖ_uãö.ßb.O+Ã+ÄhhOÄ*AÖåÃãOhßaaýOÃÜuvÖ£:ah<Cßö.ýªbîîªÃßüoåã.©ßªßb.ý©ðC/,>ÖÄO~¢bÖ/O_ÖBB.>äßb.îuÜr|©b.üÐb>£@.ãü£._ßBü>UãÄöa>ßCbA>Uý>¢î|ÃßÃCbCªÃ|_u,_ªßvZüÄüh,<ðö/ªAhä>a>ýBzv+OÐCU:ü~Ö_/ÃýÄ_ðö.ZzÖO¢@|_ÄAuå_@>ýðÜýäåö>+rîO*ooöB_~ßý_O~r|ÄÖð+äýðOaÜÜðB_+rªÖuÃöoo>BÄ¢©aCÜý@.v£_ªãbßãUðaÄzã¢|uUO</vårb/ov~Ðäª.ZªOÃaðhåBîU>,,ßA:î_ßAýaÜz_/ð|v_b.ZAU:åuzA~z+¢ÄÃA+uCß*Ü,Zª.+<@r*ß,rbB+|ª|ÃrÜ<Ã£h¢ä,|zhÄÐ£ðÖ£b_ÖßßU>ý/¢ªÃß£~CCýð¢üUÐ¢C£a©©<:o¢î_Aö.örO£ÐaUÄÖÃ~~ª£aCÃßßCÃOuß*öo/bðB_OÐAÄ/z_Ð+/îzbbäßo>rÐußÄÜü+zÜãã£ßªb.å¢ZÄ+ZbÖvvðÖUB+v~Ð,z+aUÐbaÖî_ÜBÐAÃüOäßã/ob/@b¢bÃ<|bBrÖAä+~£+_ou@OOÄ|CO.ßåöhZªÖ©:öb:üÄý@Z©Cãvh>îaãð*äî@~ZhUuvªÐv¢îO.<ÐUßOÃ.îa*h£,bh<Ã.ß_:>oß:ÜUÃð'), (0x31F75320F33D8E49D8560AA9047EBDA121BE4D290B8E23DB6A1B22E7C09835A732F1564E4A76F1565B628282F3F5018ACD9E63520F14B1A3D377B5D81AA6883F20637712276F56AACCF7CDA80CA1E8751997192235E3971E40B37BD3C1193BB4D94262907DEF625E47B88618460896318F40BE838D660A3DCB59D174F483E9B2BB19CECA3E57F72A34BBDA7C5D7FD8371C4943805BB827733724C5231DEE6AC3CFD35C2C3ACCF33748944ED28B8DE04B819A58AB3F685702F25B921A92D40C), (null), (0x8922ABFD50560B38C66954E8AA4479A18396BE84667C5A3ADF3AD8A57488088174DF5628D69C0755333090999D72E7D5193288B09292470DC8711E167D66C43E61D373CAED50CBE78E695036EBC09C6C52038C41FF58EFE1984960657EF1D7CD6C5237C9FE1CE7C866A1212842112207D4B1C379DA823E08E49232A4F066699C9182CA9889D36D351BC3DB5E36C69F87275891CC2D5EF3E9132EE56CEA43D800388496BF46897E00448E5D16A7E0D21DF3AA7D97D59C6F338FE0855AEE014AD8EADA25B230DF409DD990879F00DC42325CC7793FC5402A9B156230660424124243852BCCBA1FED136AE0BE4514D033B29E52FD26880B12AAAF8A9CCDAFDA99B57D445B7AB175783DDA65D5E582E586452F7DC7B227ECBA3873811C30831F64904609C7CF74B80129DAC04B22352C28CA03579E3112E78D918A2207305847B9BB01459E781EB6D0767C9ED766A098034658522C2C80327D5F7B354552B1D1D092069021F08DAFAB4C8628B4740F27F1F11FC1D7051B8D3BCEC73EFDE8717EC5ABCD45600F7712B8A9A7DFA5577D095054445333EFF9CA77785552294EB2C2DB086309A6C9C6E17A0C082D5E3BE8E350BEEEA8A41D4AD5B102CEE7BA24E969F6E4039DF47463868D63D926E6CC6C3B9071016A60091EBEBD8D57A3FBDE2655239F3779374AEEDC5B671B404323EEE596925748237E09EDC545E0661DBF8BAE3D83034794F2044683250FF9EEDD7A999F710D6887DD32A4964AB4F26A87BF9BDB6C03C020A750A48E11C5FE6E0FFEE8CA46C7ABB222275F505FB14A3E11DBFB65741D82DABA79F82169DA76173435864BCB2723BF13E9B945A46F0F505657C35C998FAF27B0D10CDCF8A4948B0471C292BFF72EAB83B528B643D364AB8021B035D414E9B3B93CFBE1756F089A070945C5297CA06F3927D8D466F8E3ED7C614CD6F45EA6FF981ED972FB4D2614BC7DF6457494CD47D5CA1B99FBD080A1810305424304C1D3E7FDE3430340DF3C959779F247DBC46C8BA7B600D70F55B8D22F963174263CEABC235D76B1894495EBDF0364578E59A53E98B69B4EC0FCD7A82B70D3AAADC76E5B8F35247E4DBB01632115F80CF06144D5F1F2DC70A09CFB2685CE861970EB3D179409C7583527F0DDB5382CAA04CBB7CA2CFEB8BBEAFEBAF33AFDF87F35E8F336D8E077E799EB9FCA01FFBA676F5022BA16BBEC7132822C964ED4), (null))";
            break;
        default:
            break;
    }
    return $query;
}

function Repro()
{
    startTest("sqlsrv_data_types_fetch_binary_stream");
    echo "\nTest begins...\n";
    try {
        FetchAsStream_Binary();
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    endTest("sqlsrv_data_types_fetch_binary_stream");
}

Repro();

?>
--EXPECT--
﻿
Test begins...
Comparing data in row 1
Comparing data in row 2

Done
Test "sqlsrv_data_types_fetch_binary_stream" completed successfully.
