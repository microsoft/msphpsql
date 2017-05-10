--TEST--
fetch columns using fetch mode and different ways of binding columns 
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

include 'MsCommon.inc';
class PdoTestClass
{
    function __construct ()
    {
        echo "Constructor called with 0 arguments\n";
    }
}

class PdoTestClass2
{
    function __construct ($a1, $a2)
    {
        echo "Constructor called with 2 arguments\n";
    }
}

function FetchInto_Query_Args()
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
    $stmt = null;  
    
    $sql = "SELECT * FROM $tableName ORDER BY c27_timestamp";
    $obj1 = new PdoTestClass();
    $stmt1 = $conn->query($sql, PDO::FETCH_INTO, $obj1);       
    
    $obj2 = new PdoTestClass2(1, 2);   
    $stmt2 = $conn->prepare($sql);   
    $result = $stmt2->execute(); 
    $stmt2->setFetchMode(PDO::FETCH_INTO, $obj2);    

    VerifyResults($stmt1, $stmt2, $tableName);  

    $stmt1 = null;  
    $stmt2 = null;  
    $conn = null;   
}

function VerifyResults($stmt1, $stmt2, $tableName)
{
    include 'pdo_tools.inc';
    $numFields = $stmt1->columnCount();
    
    $i = 0;
    while ($obj1 = $stmt1->fetch())
    {
        $obj2 = $stmt2->fetch();
        
        echo "Comparing data in row " . ++$i . "\n";
        $query = GetQuery($tableName, $i);
        $dataArray = InsertDataToArray($stmt1, $query, $numFields, $i);
        
        CheckObject($stmt1, $obj1, $i, $dataArray);
        CheckObject($stmt2, $obj2, $i, $dataArray);
    }    
}

function CheckObject($stmt, $obj, $row, $dataArray)
{
    $j = 0;
    foreach ($obj as $value)
    {
        CompareData($stmt, $row, $j, $value, $dataArray[$j]);
        $j++;
    }   
}

function GetQuery($tableName, $index)
{
    $query = "";
    switch ($index)
    {
        case 1:
            $query = "INSERT INTO $tableName ([c1_int], [c2_tinyint], [c3_smallint], [c4_bigint], [c5_bit], [c6_float], [c7_real], [c8_decimal], [c9_numeric], [c10_money], [c11_smallmoney], [c12_char], [c13_varchar], [c14_varchar_max], [c15_nchar], [c16_nvarchar], [c17_nvarchar_max], [c18_text], [c19_ntext], [c20_binary], [c21_varbinary], [c22_varbinary_max], [c23_image], [c24_uniqueidentifier], [c25_datetime], [c26_smalldatetime], [c28_xml], [c29_time], [c30_date], [c31_datetime2], [c32_datetimeoffset]) VALUES ((-218991553), (1), (23494), (1864173810), (0), (1), (-3.4E+38), (0.3607), (0.6259), (0.3426), (0.7608), ('bAB:+:|©ßÄ.<zýOaa@Ãr~ð>:B¢UBüÖÄýÃ<ß©ÖaÃbvßZö+/AZ:rzvbåvh_£+Ðr<Äbý.ßãr@výß£öÜ£|B©~ö©Ö/:ðßr©CB©öäßbuöö£rBðö*:/ö>>ý|ÄoUßö¢|>ý£_.O*åÜCÄ.ªha,ãÖßBý>äÐOC_üßªah¢.:©~~@Öý|£ßßb..ÃÜ.bC@|ýCî+v£,BOB|ßrÃ+ßvubC:äAöBÐCZarüaBã<|©uUC¢~.|~ýÃbý>£@Ca_Ð.îÖäÐo££.ªZ¢bU£CýðUå*vCoßý@©bAz£_ª.obüÄ¢öÖhüÄbaÖä_@rÖ£.hß~bÐ£ü~'), ('B:ä<bAr¢ö~Co<ÖüÜß|_BîårU,<ðüýüOÜ©bü~î.<<ýCÄßa£CZÜåoAäOÖrUªbªCU£ý,+>.ß.å.bbCÜO,UÖ+¢rBßoð>zÖzZzä+Ö<Ð:©oÐ@,ýÜößbüAä**U@b|Or:ýbAb~brÃ+Z:öªª£BãBauC.*Ä<öý¢äÄð_uro£>Z/ÄÐCÐ©~ðÖÐ¢.u:oýz~Ð*¢¢bªß©,ðÄ,:ý¢_ÃZ>ýÃ©C_rva:>>+Uå~îo©ß£hZbZ©r,Z¢ßÃÖä>*îbrb/ß~,oå|:>äb<äî©+Ä¢,:zãü,©ßÖ>Ü|Ããöª+ß~ðî/CÖå/_*ßãrh£vÃÜÖªãOÄ+CbÃðOU@v_ãÜÜÐöz|ö_äzýîZ¢ð.hOãÜý,r_Z'), ('UZß_Z++ä_Ö:r©Z*,,uÖbýÜB*:Üä.~r©U>ýZ|@<ªövbä.Ö¢CÄAßobzaü>r>b_@öZ*bOÄ©aÜåî¢ßßðîÃ~ß©h+ÖA~ð>aß+a,b:zß_*|ð:ÄvðU:@rAª@îvä+ª_z>vr©r/å__+>h,+Aîu:Bßßh.ZýrãCCrbÐUuãvÄß>ãbÐîoÐîuoä+£î>Ä_+åð©a~Ö/zvÜåh@ÖO:öUÜbuU|BýÃUÖ~Ou_Ä<.ÜC>vZZ~åüab£BOOöUö:h¢+~Aî@©ÖB_+:b+.z<ý<Ü£,ªBîAh~.uüªU*Ü.|<+ß£ãÜ/£¢ªÖÜä>U>Ã>UÜ>Cä+b+å|ÜrÄ~ýÜý¢ÃCß¢O:Z:.ß_ÐÐB|ã_£b+@/|>åäå.åoÜB@£ß~Ã£ªAß/üvßoã©©åb+Ä£CU~ZÐz|å|uA:rBZühåba_b:v©ÐauÐaB/,å@|ý.îv>ðäÖU,öÖ+ÃÐOZÃBAßZ¢azzo|h¢b*~ÖýÖüß_ªrÄAZÜvåAZðCÃÜ<ÖÄÜîüoÜzª¢zªo¢Äür@o@ãBv*ub:Cvu>,~BýZýbã+ßîB.üoBv*zÄª@v££//©îÐ£A,hðAb¢/ýhÃßÃðaä,böoßü@ååz<+ä|/ð¢oäÄvAhA£î*~ðÄ~Ð£|bªü|äbåUbÐªv~/ÄÜo@bZª*öð_î¢ðüoÐÖÄZ@ãübzßãaü./,*îäo|b©+öÄ£AAü:öZîýä|vîåßBrv:ª,_@h|Ðbß¢Ãb**ßOðA+*îÄb.~ßbýý*:ä.ð+*C_î.Ðå>ZzÜuöBÄåbäð_åzhöz@Ðb<oß©/ý<b.hö¢AuÜBßªCåv<,a>,<aýª~îaßaC>Bßö££<öîz@©|AhÄ,ãßaßßOA@<bß|AªUr£ªÃßoU£ý:BOåß<ö:å@oüa*©ðÜ:o|©.äÄU:î:<îu~Oa+ÐahÄýh,Ã_:vOzðÜAÐbb*Au>£+|>ýüZªßýüöð£ÐO/vÄ*UüUª@Ãå@>BÃz>@>+/£oBÖî'), (N'üªrobã|ß*Ã@hrUãÐ.vöß©@ßz_uu,Ä_,Ã+hÜ*ö£uª£ä£Ãå<Ãvb:Ä<ö@o<Üå£ãªObßzãÜb<@ÜÖb+Oßäh:b_ö*vãîÐäüz|ba,ý<Üã,AÃ¢ß|Ãö/ª*+ª£:~z£äßü/BðCoöÄÖ©+ÄAAÖ.,îhßªå>h_ªÐð¢:b+¢üåöðÜ@£ªüOÖu¢*~åbO.>buböü,@:ABz~aBbhß<ª.@O¢ÃÐîîUoA|UÃä_Ðßaü,ýr~äuÖ:C~Büý.ãZßÖ|ðÜüý*_ªCrr.<b~Ö>å*¢Uaðßb>ß:©<U+ÃðÜ./ª£ªÜ©C|uÐBA,UðüüO>|aüî@|Bð£öä'), (N'ZO*åuÐãªî,~+,Üb.|v.>:u.:ÄhbÃÖ<ª+a|ÐOðAÐu+î£¢.|CÜa@BÃ<uBbßåOBÃhã/Ür*ßbå>@|oh|~ÜboavÃu,ðîÜ<©.Aä¢|rbuäOrZÐhA~CzB|<aãAB.<¢Aäz|+,>:©ð¢ð>+ðäöÄ||rßßªÃîO,Oã_hÖ:Oaå©ð+£~îOÃobCBýb,äð.ubÐä:OBUã.<OboðüZÜOî£î¢î_äbZrÖoZßAöüoß>O|zßåZ:<uÖÃZî_ã<.|býî<¢ü¢ß¢ZAäßUZbZ@/+vZ>åCÄß£ððîåBhÃ©|£ÄAoBÃo:,ö/ýßU*bAvß>A*îoüäÃ'), (N'©~¢@©ß.OO¢z:ÖÜ+£CÃ~ãbäîo_ZßäOUýÜv¢vä,Z~_©äBoO~ªhß*ÖaßzÃßÃ<ý+ý_ýö£_oC@@vßBAOrÖÐýöÄ©bä@ýöüð:oîzu_Cäo_£.<£Zbðîßh>bü<A©*>C:£ªrOb*+@å>b*ªr+_uaÖß~boß:.r/v|r¢ÐÐåbrv~üÃ/Azbh:ÖC>:zÐzä~/,Ö©Cðu*Z/¢åzZ*ö:ßChbäÐzö<zÜCåî/äz¢ß_ðãÖOÃA£_~/ßo>åöOÄ@~£/Ð©¢<r._OaaÃ_/@ö+ü+ãBß+A>¢£aöuhßboBroCÐ>¢C*~/h£b,ýãÖhÖ|£+£hã~.~üäu£+zzßUuårü<ß¢*ý~£ü..£Zª<ã_üîîu_+ÄO@vÐbr~_v|~O~AßäaÖ¢~<~*Öä<vv*üü:Bb/ãÄã~Üßä,ÖaZU>oAî>îÜUªo@åªözrZªhb>ªýhoOäÜªrªÖ~rÜCZb>|*ãß©röüß@©boåuÖåoîÐZAa£,~Üz,orîuBªÐ©ß,ÃbBhüð£u£ðÖO'), ('ýß/Zãz~üöß£h:©Ð|ßaa@@Üh.ÄUÄh©*<O/vBaÄü,bBBhz*ßr~ð+üO.ÐÖu©a~~¢:z¢+:Äo|Ääã¢ö/.¢ßbðbhrZ+vßÄ_å*Ã,ß/£ß_üåbAar@h~:z*ªãö£@>uaUb©a£Ã+o:ß@Ü'), (N'OäACaÜuÄ+.Ov@ÐÖZö.Üvåub,zã+ÃÜð_Ö>ýÜß>+uUa,z¢<.ä/Ä@~+ßßýöÜ£ßo.|ö/ä/Ü@üößªO_v@ð:ðîýðýU+oðÃaãaã|bã</åÖA£*<ÖÄüoZ|<azÐîO>~ÃbÃ@B@hðüAhÃßbAÄåv£<u|,ý¢C_ßÄB©ä+îÖãb__ãzãÐöA¢Ö.ü¢ä¢|a*C©Ãåýüzuä,v*î*r£+Ößh.ß:üa+hãbUAz*|¢O,£|äýªå~ÖväbÜý@åÖäðZuÐä¢ã>hª*öý+ÄAvÄrðoßöårÐ@bbBÖo|>¢*ö£Üða@:vußZ@+ÖÐßÃßÄC**_CCÜ.bäO.@Ð,b¢övð£+Ã©.:aÄz,ü>ÖÜ>ý¢C£ßUB,.A.ßÜýÃBã.îbÜ©ÃCÃBbBÃîÄ£Ðu,:@Z<©_üv¢ý©uÄ£vCbãÄÐzðýöÄoaÄ@*Üb|ªuª_öu_ß,bCAå,</Ü¢ßa>b@@*Ö@_vÖZu~ÃB.©~hªoððäåðãÜ_vÖÄübb_ÄBOOÐa_£Üî:OZbÖr>rã~h©£/ðÐýýa~v_£|Ã~,üåo.ZBü<£><__uÖu>,b<ÜUýA|¢r¢ð|Ð,oß@zÃOä|©övu@:<*@zoO¢C¢ÄåhäÃå+|BÜB+ÐÐ:_z_>ßßýÜ@BhäC¢._Öbrß:_*OÃz.o:/bÖ¢bÜã©hzßÐC¢Ö_AuöÃU*z@ðaýü+ß>/ßzBa.uAã|å¢Ð/b*ð~££b:>rbÜuÄbÜåZzã+îß_ÖhßrBh+ÐªBã©b.ÄOC|<*Ouåå£*©zÐb£Ðb_*ý£_©Ð~ª/aÃãr©bðªCÐvZÖUZ<ÜäÜ,>/ªo/ÜüîªîÖÖ~ßåOßCýuß:_aÜuªÖ*båäßröªB©ßîBaarý@Äö,B©£ä_~Aåª,OOu~_aåö,ävBC*Bhß:zAv_£,u,UýZ£îÄ>v¢Ðå:>rB~zBBý|ZÜ,Öª~~C£_ä*hÜî©Ðö~¢ãÐ,U+öCðÃr_vuãöuîåzhðoüOAB*:Oðªh_,Ä~,C.£<£:/a~BZß©Öä£/ÃÃÖruv¢©ÐÜ¢.Öäh+ãÜ>©©'), (0x69C465C11581CB199C423F5ADAB10D08FBC98CA537ADE0F745A40A46E7EBF13678AE4020751008ECA0A59DA462FD031A024E5DAA578E93BBE36D7C70778431B15008E22B71ED93EAC0A005920F3B9548A1EF44998D47DEEA1B843C089397741B74EC545A1AD8A815BB72EAB28BDC95E087D8B022B9638070135CE9526220C976972887D7543078AE083DDDA0A6FBC5F3290CCE9A1A2F6408C3D27BB9BDCEAE4527A23B6354BE8C575F01197B85E0CA1796F62956522F5B68C4), (0xB5BFF13A8F987804C0629E6A7F763A1816E882E3076322C6A557F2F746D39ED5AB0AA2A010DEECED67C6573457BED6DCF44352DBFC2F79519C2A9537A580BEEDC3C8BF75D49A0837FB957B410BEE6FE68016030281C3D97B905004E649B447E6086A708EFF61075A3B1ADF67B33845AEBBAEB78F7DBB6C00EF44E4839622EE474D9539A17DE0E71F0EA8C766E371C7580A552A9AFDB8684DFE709FBA7C84C4182C981A1EFFA431DAC79780D5F3051D7D7D8C7840F75349942CEBA30C402CA28AE3AE394A57A858662450D365389359F853213DEF0C8421D942F6116302D83057244BF49BA56446ACC22AA085B4B9454FE721BD907B3C2938A988F49A96F9C13464E468E3D573B75C62FB87A2ABC7D458B0C17CE01BA0DBE821BBF21C6FAB16C9686B20D0506279736A612E80B4878CE1B39BEAD47DE119C40880C219809BECB7E5BE3A40A604084D470876), (0x5B33992F1168D4C0414C87515CC7D0A060F2CE75779BF64C4B3AAF939C8D746A5F53AD4D28EA6F3318F6A372B3EE4A3D38ADE716A844FAC03C4DE3D7B84024B3D71CF2091A827875839B5D80F784995DEF725BEFA65EC21A58ED10DB06987AD7D2F09A96FC4122B53571920CE779C8B8B386E746E4350D795AD7B02C729C6FEB2185AE07E90260AD4FA6B935CBB533DE2457277FB033F29DE2203C2591F6439801A7EB61CBC1CEB0263AF73E7BD8AEA4A3D1081A75EF3BED921024C9C63B1C5963213324C9D11362897659763CB4210A729781BFED28221260CCB318AC9AA23B8A73706F8495DAAA9F6ABFD2A58F22DC3066DB9712B4CE930E9B04D96E63906F9B871585067738C1143FFB3D03D5C8A0AAD7E745BF0B29D6A4DD60E891013B3BD94F371E7A9DD5B92D1983D2B9919038D2632F967C4BB08F5D3EE0291650301303BF63B39EAA260C012E17A587561C124D27162DD1A4BF8E9EA7C03BBCACA2DDE870CFF92DDC9A273C22944D330B080722CEDCEE3902415FFFD515F7EAEB50558B3ABD178723E5708DEEF0AE0F725B939EB919EED0B3A3E52E6FF958C7A33D51D23FC2F94DF07FFEE96B614B11924F8D7A815830D26285D796D7E817298CA8E9B09FDCD0AF6145AE391A05645157D49B206BDD464A0C7F6FB9EA62760768D55C), (null), (null), (null), ('2079-06-06 23:59:00'), ('<XmlTestData><TestDate1>10/31/2016 11:46:19 AM</TestDate1><Letters1>The quick brown fox jumps over the lazy dog</Letters1><Digits1>0123456789</Digits1><Punctuation1>,.;:?</Punctuation1><Brackets1>[]{}()</Brackets1><MathLogic1>-+*\%^=</MathLogic1><Symbols1>~!@#_|/</Symbols1><RandomInt1>0</RandomInt1><RandomDouble1>1</RandomDouble1><TestDate2>10/31/2016 11:46:19 AM</TestDate2><Letters2>The quick brown fox jumps over the lazy dog</Letters2><Digits2>0123456789</Digits2><Punctuation2>,.;:?</Punctuation2><Brackets2>[]{}()</Brackets2><MathLogic2>-+*\%^=</MathLogic2><Symbols2>~!@#_|/</Symbols2><RandomInt2>0</RandomInt2><RandomDouble2>1</RandomDouble2></XmlTestData>'), (null), ('0001-01-01'), ('0498-06-25 12:16:38.7590909'), ('0534-02-18 04:26:43.4190075+00:00'))";
            break;
        case 2:
            $query = "INSERT INTO $tableName ([c1_int], [c2_tinyint], [c3_smallint], [c4_bigint], [c5_bit], [c6_float], [c7_real], [c8_decimal], [c9_numeric], [c10_money], [c11_smallmoney], [c12_char], [c13_varchar], [c14_varchar_max], [c15_nchar], [c16_nvarchar], [c17_nvarchar_max], [c18_text], [c19_ntext], [c20_binary], [c21_varbinary], [c22_varbinary_max], [c23_image], [c24_uniqueidentifier], [c25_datetime], [c26_smalldatetime], [c28_xml], [c29_time], [c30_date], [c31_datetime2], [c32_datetimeoffset]) VALUES ((1), (1), (22772), (-9223372036854775808), (0), (1), (-2.3), (0.9047), (0.6343), (-922337203685477.5808), (-214748.3648), (null), ('ä|uÜC£r¢@Ü_@vC~bzCOBÖrö~ÖÜ/Ü~ýÐüðvbvªbÐ.özoÃÃå£rb:>åãvb©ª©A*u/b@A~ªýAz>u.Üo/AvåährÄC@äC£r/©ðb,O@U©£CZ£OÖöv>bC£¢zÜoraÄðaO:üß¢£|oaª£O,¢Ü~>AüOu,b@|ß+oåOoßz£<©*ä~C.B<><<UÃaªÄýÖÖßß.:ußZ.vã/ãäÖoBu¢:ãßCî~Ab|©hZ<Ob|ä£Uå£Öãýã¢b£¢>_ð+B_îü©îã.hv¢vý,UÜZî_ã+ýÄaßZv@.<bCuð,£oªOÐ/>>>rÐãUßäzªabaBãåo+£*ýÃu@Abýª¢ÃÄß/å@rßb:AÃ|h:ChðZßv/ãBb>ßvÄÃßUuu¢îu©h~¢bbîª©:_Ü,îhÄß*väÃ*,ÄöC.©Ü:z@_î>ßÄOr*ð>/Av>zß©|:î+b_*Öå©üÃUãrCaU>.*aªhððOî+/<ß¢au'), (' '), (N'oAß_U©Ã©uîvUã,z_ãÖ:C+/,<robU¢OÄäÖCUZObåvU_©:orüUÃÃo©Ä,aÐ£+zbª/Abr.>Ðha+ÜåOäãÖözýrã|ðaAo,C@£ßÖß©ß>¢uÖÐuC©*.ß_@oüÄbzåÜåBAOUBª¢+_ab@A+hÄ|vüÐåª¢u£åö_öÜ:öîö,~b@¢£>zß,Ða<ßãý>Aîbý@/öªu£b/Ö~Cªb'), (N'£A£*u~ßU>¢.hÄöv.AäüäoCî.ý/îbß¢/,ãU*:.~ß.ÄO:ýã@+<>oBÐCC/_hU*ßÃÖ:åraz:ªßa<b~oýÃð~>b/~£@ÃvÐãßzhuaÄüÖ|ÜßOîä©o£Ã|ü:äuÃäÜUOß,_Öª¢ß@äubäOuChÖZh~î>åýÐÖ.üB<,u<ßöOUbªZboZ.£ÄÜrÐbð~¢Ãvz>_aãrã..äß..ZöåÃÃã@Ör>+aýruA|ßªÜ@£ÄÄbÜBuÜå_ãz+îßOUåßo_BaOaãaª,äbr¢å£UO¢ðÖvýzö@Ä¢Z:ãzðßªý_Ã@bðB£,A>¢Ã>U_a¢übî_ßb©ZuÄh|_C.aîC|©~ý££ªv:îAhAîßÃüÖÄ:Ö£äÜÄ,>ã>+uÃ~hý+bü.Uö@¢Üvb©UÐäo£Z:+ðßßZvhÜÜB©*|åð<£ý+:@UAb@¢ÐUC£Oª,Cßb|UÃhCOÐ@Cð_ÜzvrOväöCîÐZÃ_î£hîbü/Bãî.zu*z@@öªBöðã:a*@OÃOßöýªr_ª¢böBÃ<+ßäb@¢ª.ßÄªªüßÄîÐ:U/©B:z¢Cßðåå£ýv:>¢hva|bÜAU.aUÐîÐ*>äUßU,|'), (null), ('Ã@îbªz@zbÖ::Ä+åbOî¢åbao|oha£UU~ã£Ä,vCrßÃÐ.ãüåbhîîoO£ß>ÜÃ**å:zäbãOªßýý¢öýBðh©OrÃCýBÐbböAhöÃößz++å*aßvuoðZur|î*B>h©Ü<uCaÜu~©AZÃoÃÖ|v*O/.åßO:äã.CßäOßh_îhÖzu|Ã|h¢¢b_ß*bÖzÄUå£AÃoðã<åoBåAhä._aüäÐzÃ|ßßzªÜÐBåBå©UBboZÐuÃý_ÖA,öð|Ö+ã_|ü>ä+ªuÃobuÜh>ªZ<ã©h>£ÃabÖö£äo+_U*Bv¢rßÃ>|@ðzüåî+¢bîÄÃAîv>Üª©ªãýÄÄ_ß.zÃUAü©öhÜÄCåbZuB:ß|zOah/OAîÄ@rª/b@AöãäÐABÜ>Ü©Ð¢Zªîvåü>rz|ãäöÖu¢~BÃß_vZv~B_u/@+Ü<.ªB¢ðÐ<ªZÄ,ýaßvähî©üöbAz£Äß:vßßrb,_oCa©,urU|îv|ZU,vªÄß_z|ßüuöAz@îOö£UCÐhUZÖÐã<.,h>Oî_r@Uäuª*A::råÖ©Ð*ðAh£h*.bß>B,ªb:ã/üÃ©ÃÜA@,Bßä|ãbß©bu<bU,'), (null), (0x9D446CDF0AF13907949CA9F85B4466D692845EA5B9BD585DA5BB591F3E0217DE), (0x90A31B0877CE46FC4A6269737DE0C578EB06F79221E0EC0F394E9819C99285CA2BCDB4C2BA890D8F5FBD73F5F4323E795AC51711E73A6CAFB70CCE28362C33B8DD9F48AF2B02BE61F96733636DF61B070F1EBF047A73E3F060040AAEBAE458328ABA1754E98F4D5707B5187F070942C1F0C719186D2E3D69BB574E27F70FA7150E00D9B2DEC61D80D067FDC55B64E957C149BDF3CA40C55B7DAD999240265FF70D0370063090E22AA998470531770E576EF765AE51B34ACE3B7D34312C8B40AA0805B758F3E2273167934A32F9BF29069F412349C1B1A659B8A1CBB25CB0FD1D589DFD2CBFB6638CCF3346BE6918D57B532E91EB68F6E8565F5D9F521675D5B6952CD95BC1FD46E9DE967A8D6BE7F0A0CC4F5E2D1F12B561B4A7CD611611755A5E8F293D3E79B956E7A80CD17C5AEB786DFE85C716A797827777A61A2FE4D2CD21202FDBBDB262529507467605E3E559BC55F4178BAE43B6F7A6C6C6D29F2702D53FB05C2B0FBABA9C33310E61217C93924D5EDAF0BB4173DC78E416EC9B7FF6E043A0C633882EAC9A397A587C3455D62A323485404C121AFE3D7FB22BA3CD01B33A2E1D80B65F091EB13FB740E9F6A8230DECBA67F9B6943185C8F6087C81F76B79A44106375573D22A678E0C5ADA1446D78E1DB6139AB9849AECFAEE6901A230474BC744B4A2ECB778E73C922787D6ABB3AF31ED634157DEEBBC6B4A5A8569), (0xC40E13CF9235A2653D2C5D275D12164F3BB9CFB5CC06F4E859F736A15CF37D13CDC9C3C797E31DFB4AFE032B81C45F59340B4AAF590F91BE1A2B769AFF01084AAE4A59944F4EB8579CD36ACE02C8679598DC6D51A9CDBABD3704AC6C6D83FC49F5B97C376ACA0AE553F21385A5C3BB52C4E0012293B7A0E0909B5C4AA24BEA19D621D8494B2F2BCDD287FEB7BA341874495AA88C81DAB53D189BA7CE6EBF430707FAAE83EA710694B5334F0CB8E9265B83E80047A35D97FC6C9A30B0885C5F1244F6AFBF859489D75313D74D8BA9842F754B4B85BFFB64A969C491267A9812DFEFC8718701CB73F2B5ED3B48FE33C2B2E09BDF282182D5C5C5CE359DAF286BA4D65949E40C8623EF8CF8B086DD830C3501DE46DEB9E9BE6DF3ABC13295D5428D829771A4EADDA65978F09B28D14EE841C2009CD1E28CEBEC9BDBF160C4F159B6B522044611674A2337EC66C84D959CA4C38070943971ABF6AC85090FEC46948696E473AE4AA12F5BB6789AF525229E063F4C9C4480C57D44770E9A8B5D3B37F058E0326821ED5A215015DEC0D6B6D089E703CF9FA950), (0x2E373C07A3FD7A41A99D45EF35E452F27BF46BE6BC6EC03D10A45A6B974608D96101F3B51D19AC17637F2C198B99B21E7DAC2E5C9AEAABC077FEF7FFA20FAA62946B8E34F19DD4614753833D41A7B176F23CEEEA18AC3F7656323D1F22AD7554D5C984200B7E2DDFD8CD1446676D7FCF4C800D6620BF4AE82B17DDBE5FB53E9ADE2E687723916B740E0E124B97F80FE8B36ED2A1A00E33157F9BF4F3A06E80228E7A67380507ACBB15985711ADFF513308F50BF1E39CFAD0FA52F8879D77D3A08EEFBC1D90D6E2ACC1168C07B622677F4AC1EBAB628F3EF172D9637F1D129B74439C8DD3290BC0EDC35EA2371FFC97034ACB2F78FDC19305F0EF8FCEFFCEE0E8355CAD70DD2FF8980BBD7600FA48DA95425F3451E22377820988E2397BF2EEF57EB902C24E26251BD3976D404B930AA715F98B9F998E0511E52A67554CBE1C18CF8082404617461C39197E4AB63636757D3A2482664E5CEFB4F0DC23BED0CE147B5470CA10CA61FDB26F65800FBA5BAEF4F1D7119A99B2), ('703362a9-202c-4b3e-82cb-469ab0780343'), ('4144-02-05 02:11:42.081'), (null), ('<XmlTestData><TestDate1>10/31/2016 11:46:19 AM</TestDate1><Letters1>The quick brown fox jumps over the lazy dog</Letters1><Digits1>0123456789</Digits1><Punctuation1>,.;:?</Punctuation1><Brackets1>[]{}()</Brackets1><MathLogic1>-+*\%^=</MathLogic1><Symbols1>~!@#_|/</Symbols1><RandomInt1>0</RandomInt1><RandomDouble1>1</RandomDouble1><TestDate2>10/31/2016 11:46:19 AM</TestDate2><Letters2>The quick brown fox jumps over the lazy dog</Letters2><Digits2>0123456789</Digits2><Punctuation2>,.;:?</Punctuation2><Brackets2>[]{}()</Brackets2><MathLogic2>-+*\%^=</MathLogic2><Symbols2>~!@#_|/</Symbols2><RandomInt2>0</RandomInt2><RandomDouble2>0</RandomDouble2></XmlTestData>'), ('01:21:33.8136175'), ('0413-05-13'), ('2016-10-31 11:46:19.9521697'), ('6050-11-19 01:00:40.4482745+00:00'))";
            break;
        default:
            break;
    }
    return $query;
}

function Repro()
{
    StartTest("pdo_fetch_fetchinto_query_args");
    echo "\nStarting test...\n";
    try
    {
        FetchInto_Query_Args();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("pdo_fetch_fetchinto_query_args");
}

Repro();

?>
--EXPECT--

Starting test...
Constructor called with 0 arguments
Constructor called with 2 arguments
Comparing data in row 1
Comparing data in row 2

Done
Test "pdo_fetch_fetchinto_query_args" completed successfully.

