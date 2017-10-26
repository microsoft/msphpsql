--TEST--
Populate a test table with many fields and fetch them back using wrong data types
--FILE--
﻿﻿<?php
require_once('MsCommon.inc');

function PopulateTestTable($conn, $tableName)
{
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_tinyint] tinyint, [c3_smallint] smallint, [c4_bigint] bigint, [c5_bit] bit, [c6_float] float, [c7_real] real, [c8_decimal] decimal(28,4), [c9_numeric] numeric(32,4), [c10_money] money, [c11_smallmoney] smallmoney, [c12_char] char(512), [c13_varchar] varchar(512), [c14_varchar_max] varchar(max), [c15_nchar] nchar(512), [c16_nvarchar] nvarchar(512), [c17_nvarchar_max] nvarchar(max), [c18_text] text, [c19_ntext] ntext, [c20_binary] binary(512), [c21_varbinary] varbinary(512), [c22_varbinary_max] varbinary(max), [c23_image] image, [c24_uniqueidentifier] uniqueidentifier, [c25_datetime] datetime, [c26_smalldatetime] smalldatetime, [c27_xml] xml, [c28_time] time, [c29_date] date, [c30_datetime2] datetime2, [c31_datetimeoffset] datetimeoffset)");
    sqlsrv_free_stmt($stmt);

    $query = "INSERT INTO $tableName ([c1_int], [c2_tinyint], [c3_smallint], [c4_bigint], [c5_bit], [c6_float], [c7_real], [c8_decimal], [c9_numeric], [c10_money], [c11_smallmoney], [c12_char], [c13_varchar], [c14_varchar_max], [c15_nchar], [c16_nvarchar], [c17_nvarchar_max], [c18_text], [c19_ntext], [c20_binary], [c21_varbinary], [c22_varbinary_max], [c23_image], [c24_uniqueidentifier], [c25_datetime], [c26_smalldatetime], [c27_xml], [c28_time], [c29_date], [c30_datetime2], [c31_datetimeoffset]) VALUES ((-568632370), (210), (null), (-283756280), (0), (0), (1), (null), (0.1356), (0.4940), (null), ('oz@v|~_åCAC@OBhÜßUÐÜBð~,Býh+Ä,öha_©hÜUOßüðaAîª<åA+ÃU.Ö<uÖCo~/oOÐBü>£/aa~bîßOð|>@_U**ÄÃÖvb/.£b¢,ßÖvððCß<A+CO>övzO£©AOããA/uu|aª+>:_a/£¢|.ÄßZýª@UzßBra/~röðU@Äîv:/a¢ÖööãÃ~bv*,_ä:ðüîÖî.+bä*b@.Cä>:+åüüÖz,ü/£öoUß.bßªÖuªª¢OUz£bîA_aväOr:+ubå@*|üãªobauhhv£:_zßU>Ð£Cbý~£ß:ÜU:Z*Bvbu.Ð<ýîß.åauÐÃh|ýªhäu<üUü+¢uüÃãð'), (''), ('_aÖÐz©C~.bÜ£Ä<ÄOuAªUoÖC+©ãÐAbÄ_uAo:~,hîßã©~väªvuhÐZhããußî.b>öB@Ä|_ª_aCßBCß.>Oã@b~@å*..,rð©Ãýb/äÖoåÐzýÐrAöraA/Ö¢bîað:uoZhh.¢*/ßAÄö©zZCObãZã.r,+ÜOb£Bb@.@a~~oªCäªhozbÜ/zðÄöbh,ÜBÜß£/ZßÄýO|hzÐ|a<Ä:uvAbvýÖÖÄ.B:vhU>ß,ÄvÄUah¢ärb@Bª*+£,ßÄUZob_<h¢¢Öb*Ü.rv|ßOv,/Ã:¢îCåÄ:Öðh,ðÖ|roÜå_ýBhÖÐÄ.bßãO|zßªÄ:£+UîoýhO.<<Ð+£.,ä/~zåo/>©£ðÖªbChãÖ_/£¢ãCý|*uöz<rÜo<.a,|<öÜU_b,ª*+zåªBýÖuª<z*ßO|*OÄü/~ÄC<rb>å+zA¢öaä¢:äBß/uß|bu£Örð++/OÖ©r/,A>ååv/Ä,ß*ð'), (N'Ð¢Cå/vhäÐ*CböOü+ÃÖ£@ÖßýðåruörzbÖ@£uÐCåb__å¢~öüªî:|îÃª£Üð£OarAO£.U+|züUUßÃBýåO.ýîª*>£/.hääzbðåã>*urz~.ZößrzOÃ.a>büãb_ÜãßOoz>oßZãz£©AÐ*ßãhªr~oö~öoßÃuãÐohZBZAvCãað>å'), (N''), (N'Öoaðð~v©Uª+z,Cbý~<@Ð¢AÜ~AÜr©©b@OOaðbãßU~a/b:îß:åä@Z~UUhÄ,/îo,¢ýObOh,OýhÜrðÐOý*ÃuzzßoÖðööåuÃ/îBr.:ä+Ð£bÃä+_,z@öÐbÖ©äbßß/our@üZÐäBÐzÖCüüªu¢ößu|¢/ð@£ÃUUhª¢:r>:~Ð~£Ãvªrrªbßb¢:äarª¢ß~<£A¢ä~ÐCÃÖ<o£î+o|ZauÄî|îo¢ÄÐªU¢uUßå£~ä+îBoÖb,Oã<AÃýÃa,böÃ£:©¢<~ð|ý>_>ß¢ößö.ßýÜ/CÖ£BðBýuåÖðüö.ÃZ¢ªß@,ÃZüubbåbüuüîu*ðü,©ã~Ü¢Ü~ãÃ_B£ßuovA/:ÃåäbBhî_öbÐbÜzUðýö¢b_.,._Ü/oaß£Oî>Z>ßß<¢ÐU¢ßö<*z|ZO*/,ðv><_zÐÐ_h/_oZAÖCr<ý*vÜª:b:C¢Ä¢zÜ@|aý*vhîýÐ>+/ZbZßoýä/b£@îA<åªCß@BÖ£ÐaÜßð*b+_vuaÖ,ãã_£:+<ð>BzßUZåîýUÃ£zvA,b_+ÄBU©aÄ>ÜvZä©.Oß@©>hbÜ>£Zü*Bb|ßoªðýÖßU/ZbÜ_ª¢Cªv<@Ö+îî:~u<üÄ©¢/u>hUÐ./aUUObzUåao+b£|ß:vÃ</_*ZÄ£<*zB>åååA@@BÄãh**:ß*ªüä/Aå~výå©ö||ªüöß_Z>OªbZ*|ãzbrî@,A><åbªZ¢åhã||oUO.|î@ã/+ßhÄzÐöîÃÜ:o_B>bÐöýåZ<rÖß.ªhÃ©ýý_ªh©Ö+Z~©ü<bB,¢hÃ*ßü_üCîª,îðCßuAß<¢ðüßZ+£.u*ah£.Ü::*ãa<~>ub+:,:äÖ/Z¢ÖÄOäb<zCÖÜî:Ö@hÃ+îOÜab/ÖÜöîvü+ª~äA£bv~Z,Ä+ãÄz¢¢ðuÜ@ãU@¢Äv/BB|Ah,O*ªÄåb.Üªßb©rÐ/ozüArãÖ*'), ('åoßªO¢rzr,:>hÐ+£îz,~@vr+ýCoýb¢ßhÄß|©_>Ä>z©Ã+*OorÜß,©a£Aªü<¢b<uU~~Zß*OÄB/¢îvã~ãAßüürUuhCbßöÃbä©Ab/CßA,ßAbÄübC,OaC:ãb>aý*:OÖ|uaA|öåO/<bý.höh_vbýÃªð~£A~_b/Ü+ã£vßbÃ.BUOÜ|b:/ö|å<b+rböz:,_,*haÄÃhÜäoBääüÖ>ã,>Oª£ýOöÄý£~*>¢zåÐîbÖä,z©b,ß¢r.:~CðöOðÖ+öbýÄ>O_©<_ýUä:b_Cåö|ª.*>ªrau|ÃC'), (null), (null), (0x67BA39352C997C2AD4F838AEB77E41F5F8BA5AE8ADC431BCC669BF7EDD2F017479C06C748AA6C6A278C4B7CCD7BD1C4B4EA8DF38F474E3A3897D6FC3DAFD4CA5F6C434F611CD6B52F4B770E90D64E4084D732F344FC87F04DCC9EA0C3702C87A7712D5138925398139D2DB82F326EEBFC5765BADC4C132E20F0845790C3CDF7DEC3E622658BBD317EB36FC496D8C242842352DE23C82E7260B537E35E28EF1CA11), (0x553BE86540AB8F786FDB0D8DE0F03AD4815EADB43A8BB79F3D89833474C163DCEF9B97816045BE2F68AED271618FAD41241C48B32E1832C9834828B6275B04AD78319D4559B8743CA07E4C8CB9463EADDC420F5778AFADD769CDFE171D07070CA924F830F168E86CA80C13AFCCA5103C0A7ADAE54CAD35C883C6DB8CB95D1E99773FE3954E0EBB7359A2AE6B9CFE0AC3BDC20CA22C21C0FBEF2FAC2B8C54741E0B50278DD4A39902E64E2AC56B387E3339122E48D2DA220C1CB08AC8A3448735080B783A4BDDDAEA95264166B58390411F3C069F25F8CD324459A7C8E2659E5398405C2CDD3E6B20808A97728162F7A8A0135CE213A4F4F79654F05082639571E9EECD74276464E0D0E61E9B605AB1E161FF28CF08BC40F34C6496894E1B9E1DF42589939483803CAF7B112D8658973BA30FEF823BC6692DADDA28C3CDDC38FA724EDED033A5A72C0A71DD102D3A0BA7CE45234A4A100A421561FE5E9F250361C993375AD53C7461A0BFBBC9BB91A1362AB0D), (0x8FEB052348CCAD1A7589CD56E7B469A02B0D291C0C1999A64408673B0C823DE597608DAD2E526486192BA5812D65B405B073369EE39BC875525EDF3F64710DF24BE8BF8627BB72D9A8A202E0741779DE54628B994C9F01FC97175BCF722522A81DB23644A62DDEF4015DF64098E7013AE501EA02E674A6ACE7C2EF0F5FA26683805AC3C1E1343727849698EEA8C063AF1620E9713A1FC1A90C895DE43DCE955EF773C03D243F59B0C9C37E50DCB703265E3FF83FB8F449609819B557D752E9B50139F63500268BD7C738A776A171CC54853685FED655A503295FA40A8902D976D9C94FA2C0CD2638B420777F4CD2BF274D60D1A1CDEBDEAB93F30CA181804DC45F309DD8222993B5AE683E06857AF8408DC03C385662F1936F3FE433DF61E75E76F5363B734B98B4A7B8D584617DC757FFBF7F38BDF99E23E80070DE8C85364D80DF908F43BCCD412887909D8C6D32F2778D3420E2AFA17C56135373DAB9272282880381ED371343F23D0F356901967BCE7E66FF50166BD13D133930685A666E606E1B0469B0054079F02DA49F6B12557CD8A45C5228D16C1C3144D9FDE3DA97D6A0D5FBE592111D78268560E8C732D101E4B390B50A1C), ('e9428c59-d5df-465a-ace1-966de0e6fbfc'), (null), ('1900-01-01 00:00:00'), ('<XmlTestData><TestDate1>3/30/2017 12:56:46 PM</TestDate1><Letters1>The quick brown fox jumps over the lazy dog</Letters1><Digits1>0123456789</Digits1><Punctuation1>,.;:?</Punctuation1><Brackets1>[]{}()</Brackets1><MathLogic1>-+*\%^=</MathLogic1><Symbols1>~!@#_|/</Symbols1><RandomInt1>0</RandomInt1><RandomDouble1>0</RandomDouble1><TestDate2>3/30/2017 12:56:46 PM</TestDate2><Letters2>The quick brown fox jumps over the lazy dog</Letters2><Digits2>0123456789</Digits2><Punctuation2>,.;:?</Punctuation2><Brackets2>[]{}()</Brackets2><MathLogic2>-+*\%^=</MathLogic2><Symbols2>~!@#_|/</Symbols2><RandomInt2>0</RandomInt2><RandomDouble2>0</RandomDouble2></XmlTestData>'), ('07:59:20.5782606'), ('0001-01-01'), ('5069-08-25 07:44:15.8923898'), ('2001-01-01 12:00:01.0000000+00:00'))";
    $stmt = sqlsrv_query($conn, $query);
}

function FetchData($conn, $tableName, $phpType)
{
    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");
    $result = sqlsrv_fetch($stmt);
    if ($result) {
        $numFields = sqlsrv_num_fields($stmt);
        for ($i = 0; $i < $numFields; $i++) {
            $value = sqlsrv_get_field($stmt, $i, $phpType);
            if ($value === false) {
                echo "Failed in field $i\n";
            }
        }
    }
}

//--------------------------------------------------------------------
// RunTest
//
//--------------------------------------------------------------------
function RunTest()
{
    startTest("sqlsrv_fetch_invalid_types");
    try {
        set_time_limit(0);
        sqlsrv_configure('WarningsReturnAsErrors', 1);

        echo "\nTest begins...\n";

        // Connect
        $conn = connect(array('CharacterSet'=>'UTF-8'));
        if (!$conn) {
            fatalError("Could not connect.\n");
        }

        $tableName = GetTempTableName();
        PopulateTestTable($conn, $tableName);

        echo "Fetch all as integers...\n";
        FetchData($conn, $tableName, SQLSRV_PHPTYPE_INT);
        echo "Fetch all as floats...\n";
        FetchData($conn, $tableName, SQLSRV_PHPTYPE_FLOAT);
        echo "Fetch all as datetimes...\n";
        FetchData($conn, $tableName, SQLSRV_PHPTYPE_DATETIME);

        sqlsrv_close($conn);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    endTest("sqlsrv_fetch_invalid_types");
}

RunTest();

?>
--EXPECT--
﻿﻿
Test begins...
Fetch all as integers...
Failed in field 8
Failed in field 9
Failed in field 11
Failed in field 13
Failed in field 14
Failed in field 16
Failed in field 17
Failed in field 19
Failed in field 20
Failed in field 21
Failed in field 22
Failed in field 23
Failed in field 24
Failed in field 25
Failed in field 26
Failed in field 27
Failed in field 28
Failed in field 29
Failed in field 30
Fetch all as floats...
Failed in field 11
Failed in field 13
Failed in field 14
Failed in field 16
Failed in field 17
Failed in field 19
Failed in field 20
Failed in field 21
Failed in field 22
Failed in field 23
Failed in field 24
Failed in field 25
Failed in field 26
Failed in field 27
Failed in field 28
Failed in field 29
Failed in field 30
Fetch all as datetimes...
Failed in field 0
Failed in field 1
Failed in field 4
Failed in field 11
Failed in field 13
Failed in field 14
Failed in field 16
Failed in field 17
Failed in field 20
Failed in field 21
Failed in field 22
Failed in field 23
Failed in field 26

Done
Test "sqlsrv_fetch_invalid_types" completed successfully.
