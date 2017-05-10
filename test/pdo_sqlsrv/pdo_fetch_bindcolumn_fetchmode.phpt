--TEST--
fetch columns using fetch mode and different ways of binding columns 
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

include 'MsCommon.inc';

function FetchMode_BoundMixed()
{
    include("MsSetup.inc");
    
    set_time_limit(0);
    $tableName = GetTempTableName();
    
    $conn = new PDO( "sqlsrv:server=$server;database=$databaseName", $uid, $pwd);
    
    $stmt = $conn->exec("CREATE TABLE $tableName ([c1_int] int, [c2_tinyint] tinyint, [c3_smallint] smallint, [c4_bigint] bigint, [c5_bit] bit, [c6_float] float, [c7_real] real, [c8_decimal] decimal(28,4), [c9_numeric] numeric(32,4), [c10_money] money, [c11_smallmoney] smallmoney, [c12_char] char(512), [c13_varchar] varchar(512), [c14_varchar_max] varchar(max), [c15_nchar] nchar(512), [c16_nvarchar] nvarchar(512), [c17_nvarchar_max] nvarchar(max), [c18_text] text, [c19_ntext] ntext, [c20_binary] binary(512), [c21_varbinary] varbinary(512), [c22_varbinary_max] varbinary(max), [c23_image] image, [c24_uniqueidentifier] uniqueidentifier, [c25_datetime] datetime, [c26_smalldatetime] smalldatetime, [c27_timestamp] timestamp, [c28_xml] xml, [c29_time] time, [c30_date] date, [c31_datetime2] datetime2, [c32_datetimeoffset] datetimeoffset)");
    
    $numRows = 0;
    $query = GetQuery($tableName, ++$numRows);
    $stmt = $conn->query($query);
    
    $query = GetQuery($tableName, ++$numRows);
    $stmt = $conn->query($query);

    $cols = array_fill(0, 32, null);
    $stmt = $conn->prepare("SELECT * FROM $tableName ORDER BY c27_timestamp");   
    $result = $stmt->execute();   
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
    $stmt->bindColumn('c14_varchar_max', $cols[13]);   
    $stmt->bindColumn(15, $cols[14]);  
    $stmt->bindColumn('c16_nvarchar', $cols[15]);  
    $stmt->bindColumn(17, $cols[16]);  
    $stmt->bindColumn(18, $cols[17]);  
    $stmt->bindColumn(19, $cols[18]);  
    $stmt->bindColumn('c20_binary', $cols[19]);    
    $stmt->bindColumn(21, $cols[20]);  
    $stmt->bindColumn('c22_varbinary_max', $cols[21]); 
    $stmt->bindColumn(23, $cols[22]);  
    $stmt->bindColumn(24, $cols[23]);  
    $stmt->bindColumn(25, $cols[24]);  
    $stmt->bindColumn(26, $cols[25]);  
    $stmt->bindColumn('c27_timestamp', $cols[26]); 
    $stmt->bindColumn('c28_xml', $cols[27]);   
    $stmt->bindColumn(29, $cols[28]);  
    $stmt->bindColumn(30, $cols[29]);  
    $stmt->bindColumn(31, $cols[30]);  
    $stmt->bindColumn('c32_datetimeoffset', $cols[31]);    
    
    $numFields = $stmt->columnCount();
    
    include("pdo_tools.inc");

    $i = 0;
    while ($row = $stmt->fetch(PDO::FETCH_BOUND))
    {
        echo "Comparing data in row " . ++$i . "\n";
        $query = GetQuery($tableName, $i);
        $dataArray = InsertDataToArray($stmt, $query, $numFields, $i);
        $j = 0;
        foreach ($cols as $value)
        {
            CompareData($stmt, $i, $j, $value, $dataArray[$j]);
            $j++;
        }
    }
    $stmt = null;  
    $conn = null;   
}

function GetQuery($tableName, $index)
{
    $query = "";
    switch ($index)
    {
        case 1:
            $query = "INSERT INTO $tableName ([c1_int], [c2_tinyint], [c3_smallint], [c4_bigint], [c5_bit], [c6_float], [c7_real], [c8_decimal], [c9_numeric], [c10_money], [c11_smallmoney], [c12_char], [c13_varchar], [c14_varchar_max], [c15_nchar], [c16_nvarchar], [c17_nvarchar_max], [c18_text], [c19_ntext], [c20_binary], [c21_varbinary], [c22_varbinary_max], [c23_image], [c24_uniqueidentifier], [c25_datetime], [c26_smalldatetime], [c28_xml], [c29_time], [c30_date], [c31_datetime2], [c32_datetimeoffset]) VALUES ((1448461366), (110), (-28270), (804279686), (0), (0), (null), (-100000000000000000000000), (0.6685), (0.2997), (0.5352), ('äðubý/ö*bUah¢AoÖrZÃ_oßoüöÐ>ßÄßUAüîÖh_u*uh.uå:,öî@<BCãå,AÖvBvöC¢ZaüoÐZarüö<.Ö~Z@~Ü~zUÄCrB_Ä,vhbZaÜöä<ruª>UCO,<¢<:Ö@>+ß,ªåÜbrª¢öãäo,ü£/b,|ýãý~öß£îUö_¢ªðu.+ýÃhAaäzvzrb£ßAÃhö,ö.aöü/Z+Ã.uvUo~v:+u_ýý©z¢ª|U/îã<©|vý+bÐÄÐ©oðbbüðb_~*î..üÐÃz,äAðß~Ö¢Äå~ð.£_ßzãÖv~¢£Oå*@|UozU©Ð+ãÄÐ,*Z/vA>ªOÄ,¢bhý/ÖÖuäA<bO+||zv©vÃBª<.ýh+¢ÃvhßO£bOvUýª¢äÄðvBbÄ<O*@/Ä@<©~:ª,¢oÖzUaÐ<,baÃÃbuå_CåB£h@ö£.<Cª@Ãß.raÃöªAb*UBCzãÐ£Zªh<|@Ö<©ßÃä|¢ää,rZ<b_ööBßÜ.A,¢ß©ããa,uUî<_Ahðo_Ä,uÖC_vªÖ£O+ÖÐ+:vOårÐÜã>oü.a@@ßaðvbaß£@v,ub+Oä@oBBÖöAüßö|Ö~hhvbuäo/<Ã+£¢Ã¢ß>'), ('Z:Uî/Üãýü<C<bb+CCoä@a:A<Ö:Cv/hzub:ZÄî+£<aO:ý~î~~z>Äzãüvä/Ühý£||ãoå,ªÜ©uÖ_.>ßýbåää|üð/ý.BO:ZCu©ß<£ªãÄ@ýß©vöß:>:ä+åvCBª£.o>Z/*,B_å~AO,rO+åÖZ£>rö¢Ð~ðuö_Ðä'), ('ur|<ZO/ðå<U>hÄðu*Zýðü_ÃÄÖ:ä~*äorO|üh*va<bvbo<BÜ_Az,~aUÃÃ_äÐãzã~aZbZªÖÜîbv_åÄ<@u©Ã*|A>ªOðäv¢<|OouÃbä.ßoßU.zZ>,ýra¢boýv|_Ää+Ð<o/@B+©a/©ãä@|z¢OZÄ|åbªä>aOöC@h_::zBýßOCÐüß+Üu£/üö+.oßã.~|AbOÐåî@Z.buu£,CªãîvUbv,ÖüOoÖ,£ªî@/ÜBðaBåã<ß~OÖZüZ|ýöhz<ÜÄ:ÖÃÐÜðÜ<*Äu¢a©ßß..¢:ß|rîr:ß£©Üröa.>U.hb|bCäbb+ZîoCÐðäzðå_ß_*CCß~>vªªo~/v*b.©Äoßãhî>_AZbÃ>¢ÐªªÐ/ðzv|ßÖÄÐªÄ<~ßðUO+ýÄOr£v_rrðC|äß©¢£<+/¢Ab:Oîß~ÄZÖU/+¢å_uZAÖ*ªã~OÐîßüo<åÜÖü£ÖAÐ:ü>ðOåh@rÜb.oz©v/UäýoðÜÖCZ+©ª©Ußr|~ÜãÃ©ÄAuÃAîÖZ~B>_,åA~_OÖßî@<ð|@vr¢Ðå/ÖªhöðUÖä,ýß|BCåðoýb~BîÜO~zh@£|ÃãrbßßÐb£Z:î/îÐðß:£+A¢ÃZzãßü,¢Buußzö+azbOB*Ühr<ä<©>ÄäZ@boîÖöüÜÃaOr©ªªß|ßC~oðvîoC+ü@ÐC.,özUß:v+B//*ªð_åUÃBÃ~ZC~ÜUÖ£~avöåÜãÐZ+ýC_Ö>Ö@özU~>Äã£:ßBßÐ<_Ä¢ÜBh<:ãbßªîÃühãß,bßö>bBZ:Z:öÖð|å:/ðü,ªA@>oÜîßhÃZýZ/££ªr|ÃßÄOýåzåAAu|uä/ßAz<zZr>Z¢oö<>îuär+Zªåä@ªãåäÜîCbCä£¢*CåU<Z.|ãðä.Cß,*å~ßîoßä</*Ð.>BãªãZåÐzCßZ<zÃÃý>Öü_ßO|bÖ:|U.ãC~abÐBU|ãð£aÖÜ_/äCO>öªÜäÄðv|Bã*>ðZÃÐ<Uö~ÐððAåÜzoAäýîCªã:výuÖbýCUO*a©ªÄ©©b¢UB~@îÃßUAUB<@¢hî/üuvßãZhåÐB©|*r*ß>*ÐåÖB|öãöåhÄã,_ÃvÃ/ßß/ß/£o~hÃÄAÜ©aZ.AÃåÃzýå/aoîÐhäb_îýÃðð<CðüßhUã,bãBC©ªßÖ@ß_,.<väýã£@ä©z+<||öÄ>ä£ª¢Cb¢Ar:Ðh<å,|b©©CßZªz@,@Ð£BbäbaöåÜÜ*ruü£OÜÄO/üð@îv~Ã|bvCÐ|î,B.åB<ö@Üvz<îã©.|ßrBÖÐªh~>*.ã@©¢£*ª_ßî¢Ð><hu~ß©ãå©Ðz_'), (N''), (N'ZªC|©v¢Äß~Uh¢£o>ªvª,~Öß@@Oß*BOOöA_¢AªðßäªåaB~ÖABhbääbCÃ_Ü¢A>>vª¢,zBBahåÃ>ÐÜÃÖÐðÜhÄrb*zåðãbUýåZ,*v,ÄU£öbýoO,**ýßbÃv+Üb|Zb:OUöîåßO*:/,'), (null), (null), (N'råhð_~ãZOZ¢öªUÄb£ß:ÄCBv.raî~.bahohåÃhhð¢.©ÐªouÖOÄZ,äÃ>@BªÖÄ_©UåhZ>ß:,Að>åßZ<,ÃuýUÄåC©h:©£Üb.:.öoÜ~ZÃ/ZßÃ,îýärbÐð@Ðvö,Ö<hbCßªö_b>åhÃß.||üîrÖÜo~:~boåÄCýÜaBh¢z<rÐãä>ýb+ð_üoZbOaÄý<©u|ß£*+å+©ÄUðU@Ä<îð©~,uC¢ð|ãö~a.oo£u©ðr@CBÄÃuZAðß*ÄCßzîZa/oÐÜ_öß©zbÜªvA|Ä.|<©_î*å¢AªäåªäU*uUÖ<ÖªoðaðrUz@Zo/,Ü/£<@ßão>*ðÜÄu@vAßßu©özÐ@ýh¢z_ZÄ<uBãÃÜÐð£~BãaurýÖb>@*OÐßCðö@ÃräüArz>h©~vbåªbã<ÄvßoåvAß+v>vÐÜßrU/ÐhßßðÐãb<r:<ª£ü£r©Ð_zªðuãAýäöß<ã£o*Ä©ð+bzßßã+î©ãZB~h>ÐBhr~üîÐýhÃÖªã:zvývz©r@ßBåö+o~ãrz+îbr>bü<**ÃCbÖbu_aBZßß@ª*CÃa_~.Z</Uð,Bßîzä*åßÃahäh¢|Öð@|.<ä+.öðÜð.©©@hä/urüö>£Ü©B|h.öä||ðÃ_îu/@,ð~ö/A+¢ÐZO*_ö|bÖÄrUãß_ãbäBÖß¢örßÄ~AîÃ*£Öð/CÖ_a¢+¢ßOAoÃª.Öå~ðªßB*ý_ã+_ob,~@î+/b_ã>,OãÖo¢Aý£rüåÖCzü>_,Ä+Ü/~ªÃB|hu|ðz:Z+,*~£zÖöOãý.AðBCî@_h©>U@hÄOz.©A~zßü~oöÐ.¢ßUÖ¢vb£AC£.ÜZ£ähß/öh/|_ß.buö_öößh@ãU>åä£|ZvA£ßaOC:+@ßUOvÄöurãÃ@oÃ¢¢ßß¢£ð~Z~hv@o~bbãbª:ýîz~_|£©ßåßC*aru,@ß/ö@©barÜ©hÜã/~U£Ð<ubbvð<Ä+ooo,ã©*Uo>>uv|zÜý@AA+_B+<Üo/ÖoaAöÃäb¢ZzB>O.Öo@O©ÜZZ./Ãa¢bßîb@ð@a<:v~ÄUoÃOð_oC*rü~@£:å¢ªvÃuýã.Uª~Ã.vÄuªö@hªu>hã>Ð~ð<avbCääýî>vzZö*ü>Ðb>Ðý|hZZ.BhãÐvU<ßªz¢v/äUÄ<UßZvoo+Ü©br>:hÜã<ÖãbÖýbãhÖ©ä:££>vBî<aCöü>ü¢ovZhOã:öC.©ÄUÖ:h/@>za:Är+uzã+ßå|ß,ð~ãª>AaÄ@B¢,£Ð|ü@:ý£ªÄ|Bßö_ª~Z@BðäãäZãA<ZZö¢~üª~v,z/~hÜraÖß_>/Cr~_<ßîÃÃÃ/_*zÜö@ß+üzrýB_ÜB¢ã|a>ýåOÐAb>ßÃªÄ¢abß</buýO_Bðu<aÄÄvãh©ð_<Cbî<*|vÐ>,B<ü¢OÜ'), (0xF502D70F2F74A32894021775707AEE3D8601A0E601FF565636A220DBFE213F3B143FA70B33712EC31501D0202A6125E5EA13FCD7F33991F6AC80D88D53C82A73C3DB6130D3E20914D2DDD1002E352BD57D3AF1EA246748DBADB05FB398A16F4DD75D5D4F00F4120709E704166891C77755030F18D63F4F5C9386822283567B316D8328D0D8DCD58828E9E13C6232731CE9E85D95915676980E01BB7A), (0xB36CD3A8E468F69E792D86F0ED5E12F9611266399BF8E6A0160D90C2D6205B1638642DD08F898EB3F249E4670A66883AFB075A670CB6E9BA853292D7D834C758D270B889304269D884B24751147E95B08456C6CFC6F40A817B734A5CF7B6DBBD818C959AADFF09B99D82E2596F97A6079CE153816DF892DE65370DBDF80DE0CDD689D087E9FB03844C0D314311B012E3CC43BF15635A4F88FAB63475F14CC090A11583E5C61E1DA1DECE3460C64ECDB4252AF0B54DCB697C39488D33C68D93004CA1A2FC2D2C1DAD251E379525EFC1ACE98050C75B0B42D6AB06AB7E91EADA503B331325ABD186F80C42902F94D4564986E14A463DCBA5415ECC5026809E1C3A43E65AF1DC9C0017F957BA187B1341D6AF61F8AFA09412), (0xC6DF805F786E2655EBAD7A656DE9E1324CE6118CC54A8A79771EA66D99AFD4EA630AD621979ADE4CF22528945C4AF40FCE6E482B5E010C7B5406E85AC8B9BC3CD98F016313BF9AB208C49A9F22F61D2C2A979E2A67A88C61FD5ACC91B184843969ECBBCC4890FB0ED3952399666D585EE8BC2F301A91D43003A066B9F3090083406136363A50E3999B9056D184F2FB90016EA8F554531ED7178AC9A82A421BCA95A0590536222398D896958A3A03AC10A5D5B784610B2EEC435E1FAF863B6E58758C3BE399F559DF4A0F1CBEBD949C13DEB903548A96688A716CC1D70273119D283C15AEC7C877AF7BD83A52EE702EAEC20BD1CD3719CEDE24B1722C3382DCD6FD7F95817FEE4B154254D8B25C169B003D1315F7A244650E6E368A16564A7BF9FC63546EDAAD2FD4C0BB8BE3401B820D1571EE206DE534B9198713496335BFF7D9DFB6F8C12C2590E44036E6B882CD52B37C00E45B5A8C172E9A55539AE6708AFC93096165894BE94B5E3C2CAE4E0196BAC095E735DA9B7E529FC927D7D2F6FDD0ABEA24786BED29301699FD1E6068E099BE98D950885521F88B0EC6C0241278673E5B0263DC1D4ED26BB71E409E60F236E88E), (0x94ADDFCEF48E90FEED2E4587B4401554C9C0B58BC1F60BDC5FAC801266895C178DCFCFF21C6CC699418336F7775A9C43B83D7D1E3BF6AC9EA2036E480D7E9864D12EF9E57123385DF91C4A3DBF1464050F3D5A280B3EDB9722315018FD7E8BC2EBD2254960B7D2940420241EAB6EDE8E9F6938D0236F59935262C23FD320607B918752EDEE1BA5586AC524EA1ABCEBB452C1799346BA9F7B7992F6FDF592C1E4C0A98D231CF19F84C4DF665E4B6E7995770DED11A7CC34D6E8D6745C24A863D7033F29702FC7976022F4CF665440300A196085E7A4B03DF475B73B457D425F1C7C8E5A0B440866B0768CEEB2FCCD82A7A66A43C4227B32530235FB778C51E3DF95B3EAC5E624C510F9D57117C18AF7A735939F12FF9BF9005B06080E0DEE2324A90F1D49E3BCC181A432CA4C0A1A92773E5342F8B2D0DFF9B9E96ADF1BB6030BB14C6EB26DA0CE5D50F7DCCD55A66C580B04ABE0CDD82B7FE6BF2297ADBA64DF606C88180D8069E505620B6FB4D1D345BF1DFEA213A4E6993B8C091C9E37B09592FD6CE91E534B8B9BC6DB6BCCBCCA15ECDCD78FBCC1CCBC85EFDD80764C47CEB176DCB764EA276BD272064DC97BCC7EC2F330C25F21D8D030B0632E76CA154F7E0CBA56E012B101B46BBC4ABB7FF20A225EBD821B846E909D0B), ('00000000-0000-0000-0000-000000000000'), ('2819-01-08 00:12:52.445'), ('2079-06-06 23:59:00'), ('<XmlTestData><TestDate1>10/31/2016 11:46:25 AM</TestDate1><Letters1>The quick brown fox jumps over the lazy dog</Letters1><Digits1>0123456789</Digits1><Punctuation1>,.;:?</Punctuation1><Brackets1>[]{}()</Brackets1><MathLogic1>-+*\%^=</MathLogic1><Symbols1>~!@#_|/</Symbols1><RandomInt1>0</RandomInt1><RandomDouble1>1</RandomDouble1><TestDate2>10/31/2016 11:46:25 AM</TestDate2><Letters2>The quick brown fox jumps over the lazy dog</Letters2><Digits2>0123456789</Digits2><Punctuation2>,.;:?</Punctuation2><Brackets2>[]{}()</Brackets2><MathLogic2>-+*\%^=</MathLogic2><Symbols2>~!@#_|/</Symbols2><RandomInt2>0</RandomInt2><RandomDouble2>1</RandomDouble2></XmlTestData>'), ('03:46:33.6181920'), ('2148-04-25'), ('0269-03-15 01:59:43.6050438'), ('0001-01-01 12:00:00.0000000+00:00'))";
            break;
        case 2:
            $query = "INSERT INTO $tableName ([c1_int], [c2_tinyint], [c3_smallint], [c4_bigint], [c5_bit], [c6_float], [c7_real], [c8_decimal], [c9_numeric], [c10_money], [c11_smallmoney], [c12_char], [c13_varchar], [c14_varchar_max], [c15_nchar], [c16_nvarchar], [c17_nvarchar_max], [c18_text], [c19_ntext], [c20_binary], [c21_varbinary], [c22_varbinary_max], [c23_image], [c24_uniqueidentifier], [c25_datetime], [c26_smalldatetime], [c28_xml], [c29_time], [c30_date], [c31_datetime2], [c32_datetimeoffset]) VALUES ((1824541628), (28), (32767), (-5982062), (0), (2.3), (-3.4E+38), (0.4893), (0.9114), (0.7207), (0.4408), ('£åîýÖö£büªü<oªb<ßå©vba©<ü+å+/_rA.Oa.<Ü¢v_/ZÐ.|z~ãÐðÜÜBä¢r:a.bv£/,ðbý~ÃýAv,Ö£Äöb/Az_Äª:vZz,OAÖruUÜå*<>*ýhÐî@.+uðU,ª,ÐÐößö©ÜÃr@üvbo>äãªªðÃ*.üÄöäöB<ð©oã>@ãb.ßÐ©ß£Ü¢:Uå+B©ß©Ã.*üBaßßªÐ~zCu©üAÜrÜrA_Ürb>¢bÐ,vä>hbOäü,aîbbb:@u~î**:a:£ä|ÜA@oÜä+Z:¢b~ßoßßÜzü>ÄÖ~vbh,bãäb@r¢BðåährÃÖaåhýCO_¢uh©,äa:UC¢<z<~~~+obäöðOÜßüÖÃhbAr@r¢îããÜb>¢Ö@Ö,v/z~¢öB©©züî~åUÖCb~UßvöÃÖ_.ý,zO©a/Ã*,|:BCä_zððvåãÐ@Ãð>~a<¢ãB_Cv*hzîhrð,|ª>hðÖ£ÖßU_o©ß>Ð_ã©äÖÐ*|ýªOo¢AC©î+üB/+£vßåaãaö¢_©~+z|b¢ßUöh*|>hßhäUzã.ZOO.båÐ@_Aý£@A©ßCäÐhã>rzÄ*ÖrÃhÄzÃvZOChaÐBÖ/B+ðýB'), ('.~Ã~ßüåÄÐh@ß,*ÃöuÖ>ÄüäabbbBüß*£b+.zÐýÄ<ru:Zª~ðAÜÃüÖ*££Ü_|£>Öäðð,>/<ýAöü_vv<_~/>oA*vßªz:Ä¢ÜO+rÐ+z_îBh<bÐÐarb,oUühh*Ð_£BÜOýÖ+UÜª*|ðoa©z*Ãüª|ß,ub|,ßAråaß©/hðoÄÄ>ü.å@:aUzãß£ÖðBv,öðrý:Ð,:_B>~_,oývC~.@Ä¢_C>O.uvî~oÖüU@ÄAuA/ý+@ÃÜ:£©<ß_äåuZ©äö|üuvOð.ß/ð>|b*,bÐUA:ÐÐÜ~ßBbZäÐöãäbÃbrÄoªvýAÄOîÐO<ß@A£/ußr£>BåBðÃü£Äa£*åAB/oÐÄb.å,äßbuîr/äåã~ubÐOb+Ð_rÄ|hð>örv>BhUªÄaZä:b:ZoÖ>zßAÄýÃ*zäÃ©zÃhÖöýh+ÜÄz£+Ä'), (''), (N'Bî|ß©¢Ãð>ZßÜã.îbÄ¢ÐÃC:hßýßýo©aB>/©ÜÜB@a@bA>ä_aäðÐZýv<u|£ÜÖßå£_U>r**O£höOªu¢bövvüðb:,aßAOBCa+Ähä|Üa©r©ÃZª+ßÃu¢<Ã>>ãö~bå<.zob@Cª<:+bzö¢bzuÜäArCß|£/@äåOZ<î_vå@¢ß<Ö*uä~oÖå/@Äßuävv@ª:b¢Ðªvbª/.*Oß¢ý.vååðý>Ã¢:,<>UAUa+îOÄãAÐüßüÖ*|uÄBßãª.,~¢ü,ývuß~+,h*ßð/v|UhðhaÐ+bu©,Ã.:ä¢ÜvuzäÖ@Ou¢+Or.ÜÃ_Z+v<:ªuAîb|/îöðZÄA£rüÃ>¢Öî,+OäãîÄhCB~o*ÃaZöÄüÜ*Ã:å>+h<~ªä©Ä|räãAu©ÐÖßãÐ|Äür©Öß£ü~ÄðZ,<Öu|@uð:Ü<h:î>zb~äªoö£ovðäbaÖü@ð|©Öî>rz¢ßBð@brãz*ðaä*h/ã~ö££oîÄßüuîuÐ/|,|ý+ãÄ©uÄAÜÃßü©ª.uðz||©:î,C|ßzª~ð,£z+ß~CðÐð¢.uîäz£>aßBaßå_::ªC..:OÜ:z*u¢£ß.'), (N'bbÃ>vUOßªÖß+ß/ã*h_ßz@Or:<_ü.å+aÄOOã<Üüzårªã:öb>ð.v@v¢@CäãuAÐZßðuÐCO£ª+|orîBð*Ü>.AAaªãÖbÃbü¢|ðªª/©ªÄãåäzbÄ*bÄ.O_bÐ£ÃUß*.ýA@|¢ÐauªzÃUÐb©@oÐöå>Ã,vå:|ãZî+£*rÐß,zÃzÄC<,ÄößabC_@ã:îz£u©OoOöÄüAð@*ähäA¢O,ra|ö£|Üo,ãßåz/oh£>o@oÖ/©aZ©rý©>rv_B£©Ä|¢/Ü*CuArðÃar_<©r<~îð+å|OÄ*ª¢Üz_<öö_B./Z:ýbÐ@ý:üÐªz£bÜÜrÐä~¢Ü£//¢o_v~ö|ßAZ:öZoArU,åa<Ã>ÃoÖßußß_ß|£C+:O,ßb@ªÜzßð~ã,,,Ö.üðÃãCãhzýUÜ£.£A©ÜbaBüüBÐ,*ãu.:/hboÃOÃªb_£Ð@+ýÃ/v_oªZ,©:ýãü<ßýîî_ß¢ªuüãýoa<:U:ÐÐÄî~ÄUãCÜ,ÐÃ+Ähv_Ößü_,brZÃo:Zîur|BUÜå/O©ÃÃär@Z>vaÐðÃ/<O+¢ÄvaB.ãäo@hU<,.C|ovä@åÃarå:'), (N'_ÜbðîoOoo~ZãýAýoUß£*ßCvO*ãÐaUßrüvî¢Öb*~urö,~ýa@ãÃäübßuh.>å~b/oî+,*|B.UBîu/CåÐÃrz*å*.äCobÖuUOÖ_ßbbAr£:/::Öbä,Bv©ß©vÄ_ã>>UO@*OýðoÃ¢bßÄ>~äð:î©ßUßö>|:@>ßUu||ü<öbU:ãåoÖA:üzB.ßBðßÃý.|ä£|.Ãå,ävo_CðãO<ãðBAðA~C/bahZÐåüb.u¢å/rZ,oOßßü>öð<ýÖî+¢ßb/üªäu~_Z|b_¢|~.Ðß©ÖäÜz<ªbußÖCbÄÜ.|<<ª/hª£_Uðö*+î~ABÃäöZB@aßýÖÜo>+ÜraBðC+_orub|£ÐåðoBüaî<v+üª£hãä+Ö|OC+ß©ðÐoÄÖbAö+bCã¢b_*äÖÖÖü@Ð,z*å*ý:ª@C@OuãOÄr*aU~aýUZrrz,¢bÃ.aß,.OÄüî<¢ÐCåz@ÃA_/öUo+zB~CÜCuîbßÜrbßzaîA~*ðbaBðv©Öv¢_z|ß|hb:ªîhßaZrz<ü<bßhoOZÖßbÄ:a/uzb,aÐCz/</©OhbÄî¢ßÄZuß.o,@ß+ýÐoîÐ~ÄUßzÐãh£zÄ>ýoÃbob£ZCý>£u£u*ü/ÐvÖ<~,:bzbäoª>Ã©,+Bvã©@ªª~rªª©@@orª:Ö@~Z£bÜ~:ü.ýü|UåuÃªbÖoåaoä>£|*ý.îUðäÜß£Öåðh¢ou£A/£Z<ª@Ã~£~hÜ~hACüaBÄ¢,B/bärãaUãör<uðb_ß,|*uO©äoÖîA©oCð.A_ä/Özä~.ÖO~CðBb:rÄoZoîÖOÃ:öüªÃßZbO,OCbbÄÜã:<Zbãßürö/ÜCä¢Bubäð¢ßb,vbÐÄå*h£:_*:Zî¢¢hý,Ð.@ö*ÜbåýrßhA,BaýBBBåßÜzª_Oßz:uÜ¢/*ýÜC.öß:o/©,zbrå¢_bî@ö¢O*ß~U||üa_ªbCUåÄhZ/£+©urB¢:ßÄÐÄî.+~Üª+_åª<£ãÄaB£ÖßîªBZv_C~<£CÖvZßÄ©Ö©BCÜÖ_båa,@B_ßbåßCßBBb£aåîÖãr+aa+*ü£ÄCä|U,@Ðb,C*Ã,<Äå|£'), (''), (N'äÜrÃ©,@ÐOß.OÃUvãÐ_ðö@ß/hãåzCßÄ+rªuýÜAÃä+~©ð@ZOÜ:¢Äðb_o.b+bäÜb,ð><r~:U>ßAÃ|+ääðä£Cä@bÜv©:ãö/ýãbZOÐ/Ab¢zOCîî.üß|O/ru©råäÄr_h¢zbßOªvOC:åÜaß©,OååÃ£ZÖaß¢/bZäÐã_>ÄäbBÄßÄª/Ö_/rå|@Ããzä>z~bA~OOîvÄbU_bßzboBüAåý<+.|_ßOå_Ãý,ZabC¢_ÄoA>O©ßÖrª©uå::ªª©o££Bbã_|_Ã@zAA>:zÐöüOÐÖßªªrZß|öÃ.,üüåªbrü¢hCuå|¢Ö/CÄ©¢>/åä@<¢uÖýÐãî£b@|Ð£Ða~ª£|Äb~|,/UAßî_BAB¢ªÐåh,.ýî|Uå.bð¢ðßUzß/AÃ+~>ßCÖ|._£AhvÃ<UhÄhAZ@aOBoýÄãÄvO|b@~åªåoaã*ÜAß*u+ýoCZý,ä|zÄöÜã~ã+ã~rOå,|ßýA<OUhzãÜ*ä_oî|Ã©A|îö.ov@.OrCA>¢¢Zzüð©Zo,Bý>ÐZröA_@äß£å~ärÃãüCÄÐäUðåªªÖýÐ,@ã.Ã|rrUO~_¢ßü.ÐCÄý<rv.v¢<,**/Ü@C/hAOo©<¢ÄöCrU:ö<å<bavOU/CbbãåîÖßa>+ã+_öb_ã@zZb,A:ä<öÐ@äUå¢bCª¢ÖßrzrÖbBB*_ý</ÃðhbÃA.a/ßb>¢CöÖ<ã<ðBÃCýä¢ö*ÜC/Öb:~ü<ý/|uÐ,.UÐ_u_rb©oý*Ð+Övª~ªroÃî~îü@+üA©+öaý+/Or+_baC,.Oao/z*Ä|Zzý¢£UaäB£@¢,Ä,.ä.,UÃbÃÜO>Ü@O_Ð*©.Ð©+ß|ö*Ãý,¢ÜA>¢Ö~îö<år£r:h>@©|îOüýüÐbAä:îýBzo.<Zî/öß,O@@Ah,.:ãA/</ßA@îÄ_b@|îCzbA©rªuªbßÜ><Äð©:ýbü©ãÐª:ÜaÐ.Üå£ª.ÄB+ÐUußß,£A_brßå¢@>ð:>z/u/ª@OzðCåbýãzÄÐãOä:B~îÐßö_h+hÃürÜð@ã£Z¢bÐî©ýrü|ðÜ©Öªbrä*Äß~©O|ýrBÜb,rîOîu,,©UÐªüüuª>ö+o~boCäÄüîOð:î/îoÐå<ä,©ãrBýOªÃöü>vÐ¢>zAß*OU<rÐßßObß@Äabý>ÐåbC©CZöu/Ooß/äÃbC©Cr£<Zoý_å:U>OÖCÖ/ö©©bü_üZª£,¢öß¢î/åZB/ßö©ª.@ýöa/ÄÃ:Ö/:¢h©©oÄ_¢îö£¢~Ü'), (0x86C692589A736BD5D7741E6D8EDC33FC5A4F6C421A5C4C55BD6451787A0876B28E0BBB3043DA32E3D11102C09DAF140B4A7C978D0906B22A793D9B3521F35ACFFD6CDE822103B87F1C897108598BB70E4F452DFE70E9A8885990B6063FFCC1DEB733C230D092EA47C417708094A9D0EE858DE6DCC55B5E14C45629914CB14020C8925126C8873DBC5BE63A597927F3B0C0C881B215E5195BD4AE6C7A6958FA12D74FB80257A925FD4F2980DE21059B9C6278D084308D0AE279F5521A1AC35302EB2781296FF15816A28362EB643A39CE12D017F08876E14A44D589554060CC000EC08828B7), (0x8EBA1C29159FDB52AF42A3AC3A50B9435455115E29EC9B7BD911), (0xC1C8537B74D6971F9B7E417596A6FEBC20C5BE0645D461D401A5A11EA15004526209ED795031C6792223B9D7D0E992469CBFB94C8E7203ED667F7ACE34787E4B7AEE128DB9856BEFFF44E3211A8A0317125B81E197B7C3F006FF12D0B6F745A9A56D5DE1D7058C1753CA4C4A8EE1D2DC7B62BCD8F69CB3C22A2186BA729B3807FF3A7CAD2D223F0DCF6A8ECC6B7962252C3655543B6CB208B99AB7B67ED2A5B892454B546425AF5922AF098C0EE757B0BE618D3B45E3CC938773F3926A898EFCC4D53A262E0EC00687FB318C6225F9D24306F71D446972AE78B8CCF1321CEE99200DC739AC3182B0A187B449B240306179204E649271B75B628CAD7641CFD36A0D986B545DC0261284477BBDB428FB37876B7712CE8A06AF556CD61F830B6ED2B34A3C19D5DB03B9F93BDF4AF5F9A63A9075EFD658C19B8291BBFEF1EA078F78D9A90A6AECE262AB67BD1892AABCB95CC69C8E0D1C977AF1AEB98819DC12CEF2AC67714CEF3C4DAE13436F9AA809D8D501A22B4E78825C412F18BB325A48CE55EE26E5434B6D6C894EE0F9D827DF48A5E1B2183AE7807A2A0A2F1DD6811B54CB751AAB8223958A9436A1FF9888841A4FCF1651A72F946D6EABBB381B509608338B2E620D33C6814C2884044580E2A28615CD066BC9AD9BE1AC3DD6685C6E439F98CC0C5A210C1B0994057EAB9027EDC919B206838F9D69DE928160F6EF18EFBA4064ACB4D5476508B1FCD6C32FEF5084500D4FA967C00FFB622EC79FAC251A7B15C36B2B7B38F4D14DFED193C7A6AEA230DCA5E2C946528054B151A780C55C8BE25AAD6B84250917187E5C69EE7C34BC7F246FD07F38B2CF71EAD7C9E64C5088A246B16CF8431E0BD28EC94AEAE891D87C95275C568B0BA9A8DEF9994D3F30CB2ED4C7A8CBC8C33C0663FDF16E67E58959FE4D5125F432163A07C41A4984BD925DFD1C1621E80DB4D2EB55F28E1A3B40F870404FF2DFA639A2B16EC403A9F281A56A94714ACF6770F1987E3A9E3F871FCC8421D9436EA07D57E374707A48B128F9D34D2385C9F52908D05D5A606C9A62294EF87F87183E37CA171A675BF7F6A8E75641CAF2649A028EF7D07311461CEA4811462B5031A0E09516596EAF8DCE11DAB4730A2BD070C4B4F5F09048CA2584FA2F1F4AB0CA57B5B56134DC0B2B520129BF995CD211BF78A082775A401097CBF0D473806E84600F5DBD55C99B94B988A3EF668930C9B1E96FA6A92E53FC74A4C969E36B8D292A55E8AFF15BBF5DD0CC8EEB751AA0E9A26238A10C7DA32BC6049B10031A882D0D05CC02AA6CA9C2D79C99C65654523F9BBCEC5132EC1CBCB2CCE9ECC0AF5AB64451EE7F4EAE3F45B865C96FD3DCC561B25B84563E26FA10E91F5FDD82BD023841E381F465D2E0FD7DA6A8CD1240CE0A), (0xDB77416314031168D028CBD1E9CE16232C86DBF947B3FE044AE2B215E69B415F8A0FE62298D220172A1BACB3C4C6DBF478C614482F8DA728793D183251FB8135E66B47DAE8F48A5966C3CF2B064A3F8406BE9C1F87F92FFC68BDA95ABAEDC62E9A77CC587F010C843BBBE26CBE7B212FFD942B935E62AEED3B4A712A4A309F78FCA0918F7DE60405B118E3CB17C23EEC9C7A5C25C133CF38C9DF1D348FE23A0B1B69701DE991B1179026E1467EE2D6790C848DD0D379B7C8D059C59DC9D076CFDDD8AFAD5221030AC845959B5289B9E708A3280A546938A1B2A74C90CC144DC0B01168A6322926EB8182078EE41C76479FDE9425A76B7B74A00DFAAAD4723EB4F7D5EA402013B4DF7D3D88D161A9BD963104F1ED7F28184496F406242E7B160F4353960F3136268B29B5A86AE3A912F6506479D0815135CFBDA6A92C0D90B10126798E0E12AE64FADD65C4CA4561208EBF933D7D472DF203F1509C7CC902970A9BAA57F47A0DCC23043399B4BD1017305A094F4627C97AF6376439A9CCB595ED544209ACBB0C13B2C61A7913933D45E1396B21BFB351E2D13060BC16D322564E9EAC607B076B744EF69D7F0D506662F91E58706094D53A025F377C5A4FFCDEA3C32B75E7DCC9D9CDF6D3C915D1D581C9FEC3F77BB2F5BAC9725D1CCFEF19FEF9BEDC4A24F17A1C547EA26AC00B243189B5B25C8C0563F1CD71AC757FAD0ADEDCD5D056F58B5DD883A6A2FD0F8BF9BD4D2C93193CBD71F45BEA7CB344059DA773C48DEA3BCD3CFE58D9), ('99999999-9999-9999-9999-999999999999'), ('3033-06-11 22:03:27.832'), ('2034-03-25 03:58:00'), ('<XmlTestData><TestDate1>10/31/2016 11:46:25 AM</TestDate1><Letters1>The quick brown fox jumps over the lazy dog</Letters1><Digits1>0123456789</Digits1><Punctuation1>,.;:?</Punctuation1><Brackets1>[]{}()</Brackets1><MathLogic1>-+*\%^=</MathLogic1><Symbols1>~!@#_|/</Symbols1><RandomInt1>0</RandomInt1><RandomDouble1>1</RandomDouble1><TestDate2>10/31/2016 11:46:25 AM</TestDate2><Letters2>The quick brown fox jumps over the lazy dog</Letters2><Digits2>0123456789</Digits2><Punctuation2>,.;:?</Punctuation2><Brackets2>[]{}()</Brackets2><MathLogic2>-+*\%^=</MathLogic2><Symbols2>~!@#_|/</Symbols2><RandomInt2>0</RandomInt2><RandomDouble2>1</RandomDouble2></XmlTestData>'), (' '), ('0338-01-15'), ('7589-10-29 06:22:50.6660670'), ('0001-01-01 12:00:00.0000000+00:00'))";
            break;
        default:
            break;
    }
    return $query;
}

function Repro()
{
    StartTest("pdo_fetch_bindcolumn_fetchmode");
    echo "\nStarting test...\n";
    try
    {
        FetchMode_BoundMixed();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("pdo_fetch_bindcolumn_fetchmode");
}

Repro();

?>
--EXPECT--

Starting test...
Comparing data in row 1
Comparing data in row 2

Done
Test "pdo_fetch_bindcolumn_fetchmode" completed successfully.

