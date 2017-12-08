--TEST--
Test insert various data types and fetch as strings
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
﻿<?php
require_once('MsCommon.inc');
require_once('tools.inc');

function explicitFetch()
{
    set_time_limit(0);
    sqlsrv_configure('WarningsReturnAsErrors', 1);

    // Connect
    $connectionInfo = array("CharacterSet"=>"UTF-8");
    $conn = AE\connect($connectionInfo);

    $tableName = 'types_explict_fetch';
    $columns = array(new AE\ColumnMeta('int', 'c1_int'),
                     new AE\ColumnMeta('tinyint', 'c2_tinyint'),
                     new AE\ColumnMeta('smallint', 'c3_smallint'),
                     new AE\ColumnMeta('bigint', 'c4_bigint'),
                     new AE\ColumnMeta('bit', 'c5_bit'),
                     new AE\ColumnMeta('float', 'c6_float'),
                     new AE\ColumnMeta('real', 'c7_real'),
                     new AE\ColumnMeta('decimal(28,4)', 'c8_decimal'),
                     new AE\ColumnMeta('numeric(32,4)', 'c9_numeric'),
                     new AE\ColumnMeta('money', 'c10_money', null, true, true),
                     new AE\ColumnMeta('smallmoney', 'c11_smallmoney', null, true, true),
                     new AE\ColumnMeta('char(512)', 'c12_char'),
                     new AE\ColumnMeta('varchar(512)', 'c13_varchar'),
                     new AE\ColumnMeta('varchar(max)', 'c14_varchar_max'),
                     new AE\ColumnMeta('uniqueidentifier', 'c15_uniqueidentifier'),
                     new AE\ColumnMeta('datetime', 'c16_datetime'),
                     new AE\ColumnMeta('smalldatetime', 'c17_smalldatetime'),
                     new AE\ColumnMeta('timestamp', 'c18_timestamp')
                     );
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }

    $numRows = 0;
    $data = getInputData(++$numRows);
    $stmt = AE\executeQueryParams($conn, "INSERT INTO [$tableName] (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_bit, c6_float, c7_real, c8_decimal, c9_numeric, c10_money, c11_smallmoney, c12_char, c13_varchar, c14_varchar_max, c15_uniqueidentifier, c16_datetime, c17_smalldatetime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $data);
    sqlsrv_free_stmt($stmt);

    $data = getInputData(++$numRows);
    $stmt = AE\executeQueryParams($conn, "INSERT INTO [$tableName] (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_bit, c6_float, c7_real, c8_decimal, c9_numeric, c10_money, c11_smallmoney, c12_char, c13_varchar, c14_varchar_max, c15_uniqueidentifier, c16_datetime, c17_smalldatetime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $data);
    sqlsrv_free_stmt($stmt);

    $data = getInputData(++$numRows);
    $stmt = AE\executeQueryParams($conn, "INSERT INTO [$tableName] (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_bit, c6_float, c7_real, c8_decimal, c9_numeric, c10_money, c11_smallmoney, c12_char, c13_varchar, c14_varchar_max, c15_uniqueidentifier, c16_datetime, c17_smalldatetime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $data);
    sqlsrv_free_stmt($stmt);

    $data = getInputData(++$numRows);
    $stmt = AE\executeQueryParams($conn, "INSERT INTO [$tableName] (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_bit, c6_float, c7_real, c8_decimal, c9_numeric, c10_money, c11_smallmoney, c12_char, c13_varchar, c14_varchar_max, c15_uniqueidentifier, c16_datetime, c17_smalldatetime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $data);
    sqlsrv_free_stmt($stmt);

    $stmt = AE\executeQuery($conn, "SELECT * FROM $tableName ORDER BY c18_timestamp");

    $metadata = sqlsrv_field_metadata($stmt);
    $numFields = count($metadata);
    $noActualRows = Verify($stmt, $metadata, $numFields, "utf-8");

    if ($noActualRows !== $numRows) {
        echo "Number of Actual Rows $noActualRows is unexpected!\n";
    }

    dropTable($conn, $tableName);
    sqlsrv_close($conn);
}

function getInputData($index)
{
    switch ($index) {
        case 1:
            return array(array(-1512760629, null, null, null), array(167, null, null, null), array(-28589, null, null, null), array(-1991578776, null, null, null), array(0, null, null, null), array(1, null, null, null), array(0, null, null, null), array(0.0979, null, null, null), array(0.3095, null, null, null), array(0.8224, null, null, null), array(0.6794, null, null, null), array('~<auu*,/Öb£bbör,Aåbßå©+b_ãä¢ä*b<C.Ä/v£*,Buzößý~:ZÜb/Üå£îBðÃ.>Ö~.üoö©UßB.|ÃÄ£*/v|U/*bZ£ÄUÜß*+ööî*©ðü©bðr@Ã©åbOý|©©hob/>Cz<Äå::Ð<¢ß+ü/:ª@zrß.¢Ü£bÜU©ÃßÜßðoß©r*bÜböOUvãahub£ãäªb>_ã£BOÜA©ãü/ß¢ß.ov:Ö<:_+uÜC:£oöü*BzC,Äö~Zî@/Z/r@/©<~.ã¢Aa</*bz.@åýBÄZÃA:zå<~öBbß|ªaýÃ,~><vBîv¢>ü>ý_zz@rÖ¢aU@,ABð/¢ß>z/ã@/ªUA~CoÄ,>bö|Ö>A,v+©CbC/Oo>©ßa©boAîÐvOo>ã|Cåöo+ÃhÖBAbo,+<ßã/£@å+ßAÜ@äÖÜOBäß~öu<aßß_bð¢ýý£_U:*Öä*©Übð,ãß,üððr+ß/U*ã¢ãüß:rAÜåz>*ã+a<îoo|¢üýoBaÃÜ£ãCaC@ha,äzäî¢ü@å£b~råîUbßr©ãßÐ:@UhAO>u*uýBbäZ£aý>v:ðC~ÜöåðzZ>O|Cä+£>öz./Ö+uÜ', null, null, null), array(',ßhr©+|v@,Ã+BZ|îAÐß_öýða_AoäAOÜ*ýC@hoBßßaä+ýöCäAä_Ä¢/Uî.äC©¢rÃuz¢*,ýß.Ðöðý@b£öb.OCý@>hðÖrCZb/Oªz¢A+ªÖäu<ßÜÄ/ÐßÖîbU:bÄÐã>/£ÜÃBÃ@Ð.r:ªª>©zî_ÄÄ:@A.+.aoÖ@¢åOåOBB|+Cvüa_+hz|~COoACAî¢+*Ä©*ýî~|.Äz|u+o~:<@>Arb:~£z<äbãv>Ðr©:ðýCößÖ¢UAîãý:Ã~.C*C¢uÖ*~CÄ*äAb>h@h_>,|u<<r|Ö><.,vå,.BAuo£_ãB.Örö.Ä>zoba~C©hArªB£Zü~oÃbb>î+ääÄCbÐýª*Üýburäßv/åOüA:Oß:obvz©ý/ßroäaª/bªvz©rÐ,Zäß¢ªÄ.ã.@z¢|ð*aCý©:ýÄövã<h+ÜC_ªÄßÜ.@b,Ä,,Ö+ÃüäCvUrÃ_Z,ªî|Üh|bbvýÐðÜoð@bªüb¢öª~åªAB@ðäb/.O@üvUh*z>,öAbö+ÖCb~uÖ£züî|_ö~*CÃ>+ý/_ß+ãÐz<u¢ã@bÖÖßß<r£_Oý+Ã¢,ÖhUv|Ðüð', null, null, null), array('ÄÖ@:OB*bA<rß_*rZÄ:u|:©u*~zü+vßBß_@bÐÄr+B£Z©hOð¢îbva@Ä©äb_oääCýßU+,ZAv<~@Bhðhabh©ÄbZaß~Cä£Ar_*@ü£@.ßß¢~£ªÃ>/|ÖÐA<arhbÄÜ<Üv/,UUAoªov£ªv+Ah.Ã*Zo|CðýÖ_h+åöÐöA@Or.*£*ã.<b:O©oa©A:o¢Ðz<U@@AuZ*î£Uããða*Ãüzo£ã|~äü+uîZß/ßåÐoãCZªBhOßößzb©ÃÜrZ~©¢Ãð£båb/¢äãbürã@|©Ðb@©Ü¢b©v|u@~CruB<î<UÐAÄ|öCÖ+äãOr||_hüðzª.ª@OBvÄãÐB+ªb/aaßCß@ZZ.ÐbzA~ÃBh|ªåãC__:©/î¢Ü<ªCC|@+äBßZCrßhßuho£üßýr~î>v©ob.©£v<OðaoÖv:ß~ÐîÐ.£:ZÖýß£ªð_©AbOöÐî:|_©¢zCýîÃ+r¢äýÖåaÄß£Ãä¢åU,+C©B@bÃv+rÐACoÖBBv,zÃ©ZrouðUÜÄîß+öÜäUªäoö~£|hý~£åªÖ+>öÃÃã:ðvC/@£Zð©O,åã@.u,BAÐbO£Z,Oü|Z/UBAß©ªA@býUuz.UýÖUöv|ª£B,Ð¢ßb*ÃÐ£UðZäbãßOäAA>ãåUb,/Ä¢aä©:ð.öýr<Ðz@~b<öhÄ.CBÄ|ª¢|ä+ÜÃîüC¢,vãö£îãü.ZÄüUßßBuÐz>ðoBãZäB¢:ßã+.üÃOBOb@ZCZvî©ZA~©:O©£ÄuOý£Üßr_¢<+|ZhªýA©ßOßäãö£~bAÜ*©ÄÜv:aB|ZÄbªÃß|BýåýÃ.@ä:ð+*baOÖOÜvß£ßAÃ~å>,bCOoa._~obböhãz*b©b|Büîã/aîzür.ßîhß*Ðr*£<ª~CÃz©ß>ÃªîãövCr+Öa@u¢ü£ZAabü¢å*.b>,ÜhB*Bßvª>üüäÃh,:zhUý¢<.v£Ö@bÐ|/äÜÐ/r£Ã..*,ozbo,ãU/Avã@ÜzÖãÄ+zräö|CböÄoß£Öã©uzbr>+ÃãZ_/ruÖ©Öß.ðß/|ýÜbä/Ð,ßr:Ü~Ðßüh,ßî+ZoÜ/©oaÄ:>O|CÜov>z_£vÜhý:©aåß£aBðbUßa©OUÐ©,Ðvr<ö¢Ã©bZUB_aßüaoCîß<B£b_*vßCvðª,/ÐurbUCýößÃüÄßuÃOBªÜ~vo~./v/ãöAz@hªbÖßBåßß@ZöBß_ACðO*ãßh|£ß£üÄhaÜ+:~uoö,ýÄüÖÖå¢ZªÖ,ÐaãýÃz:_Ba<îBÃ>_ª£UzåO|O¢äb,©a@Au*/að,ÜUÜ£ã,_ßåbv*A£+:>|:©+ßvbäb>/ÃÖv<¢råý_U*o£ßüýC*ýäÃÜãå|UaöýÄÜÖîCCCßßÄßÜ,îurZa|ãvö@B¢Öîî.ð+Oðaã<~v_Äär*£oBzå¢©vU<CÄÜv©v+rr.å*¢ä,bÖ£a<ãýb¢åö_:AZÐ£CÐßZbrîao@£ß<Z_UðßäaUÜ¢C£o.ÐÐðoð©uuîAba|ÐaZ*+vhª.Z@u/Ð.*BÄ£îßOZu*ÄCub¢räßzvu<ZÖ+aßz//ö>ößåß*:CÖ_Ðba©oî_ßB©/C:oäßövåaåäß,rO/oßä.©~Ð@.vBrBU_*Ü_,@£Aa.oUÐ©äU£:ü>,ýUUÖ|Ä©:_ãbÜÖ+_Aãã©Ühß:U/ãb£o£©u¢å_B_Üßßý~>|B<ýz*rßªzÐuOßa*Ä¢aZîOãoäß/@baäÜ~zîhüäÜZöãbð', null, null, null), array('54e16f51-64f1-4d62-a028-582b553c2de5', null, null, null), array('2130-04-16 14:12:00.131', null, null, null), array('2032-05-10 23:32:00', null, null, null));
        case 2:
            return array(array(-1111886816, SQLSRV_PARAM_IN, null, null), array(27, SQLSRV_PARAM_IN, null, null), array(-20174, SQLSRV_PARAM_IN, null, null), array(-840346326, SQLSRV_PARAM_IN, null, null), array(0, SQLSRV_PARAM_IN, null, null), array(0, SQLSRV_PARAM_IN, null, null), array(0, SQLSRV_PARAM_IN, null, null), array(0.4880, SQLSRV_PARAM_IN, null, null), array(0.9184, SQLSRV_PARAM_IN, null, null), array(0.6916, SQLSRV_PARAM_IN, null, null), array(0.7257, SQLSRV_PARAM_IN, null, null), array('<ö©ååä,ääÐ*bhîvr<Cý¢î©ßZÐ_å©ÐZ,UC:<öa</©bU*Uö@u*AðaßÃBÄßh+o+üÐC~*böÃ:*OaÄ*|£z|+rZ_¢ßv~~aÖ©ß£ZbÃ+CÜîä~|îÄÐaB+_b>~aöb/BzZÜ@öðß@_Ä££r__£>£Ð£ðbU<r<Äou£+ývUß<h+£¢BOrz<Ä*@öö@z*ÜoubZZ<u¢Zå+£,öÐb@haB_Öåöa@hOuZhA©>B~/ãbo.>îzöã*,ßå/+zuu.+BZßzA,aÖzüåão£©BãÄbä~ýooÜ,+äßÐ:UÃrz|vä,Bå~¢ä<_£uÜv<_O|ßBC¢_£Ahöª_¢oözCßýzöüý+zÄUÖhB@Uîbh/u/©zÐbÖ¢A*ã,Ãî£<>rUªßÐßîZîåb:+¢|A_BÃo©ªäu,*ýååbU:bÖÄß|¢>¢ÖaãrÃO©Äv+oßöZãª,+/.ãa/ã£ª,¢ðÐ<î¢b.£Ü©_r©vª@î:>ÖðB:OrBÜÐªý|bßbÜ|åUOåîOãÄãuÐ|/îörB£ÃßZZÄ@Z©bÜB:.¢@b££U¢äÐvÐ+ý+uzÃb+üo+öv~_©~Uhbª,ßCb+UZö>Üü', SQLSRV_PARAM_IN, null, null), array('|öÐob*+ÐÖ<UU|ßbCªb¢ªuCüoUOü:Ü/>,..Ä¢ß@>îß*äî|å>~Oo+/o+*/ü|îî,ðö*ýåãob:zb|Äßîvb¢,Ã,UªbbrAbZ©uªª@ä,_ð©A*>Ðävä:|:oîö_rý©+vî©ßBßßb>üOö@Öoö*+î@ÐßrÖ<¢hÜZb._raUaýUUÄößßîU¢ð.ÐýrãBh¢>Äðz<©AÜ/|©Ö@>hüBCO~öýZ>äÄÐAzä~/b.ÜzbðÜbða++ªå/ð~ACÐî~©>./<Ööý<~ýuÃBÐãåo*h©ö£öîüZß:ZÐä_>Ðvî©<aBð_ß++OzOÖhö,ÜîÐä<_><h|OãhÄr+<öuÄ*AÐ.ä~_Üý¢Ähß<Ö~a.:Cü|ü++öu©ýöÐßîUbÐOzaåýÄ>_äbb©ö¢*b@BÐÜb+bî+åßAåîu|/A.Ä.~hvb:@zå|Ä,ªÃZß@v©ßvB@Bð:£öß@uðr££ðü<Ä¢äÖaßO.:rª/Ao,ª:ZbA+¢ß|>,*ßoöA+ãb|Aü@bÄð@a:+,<b_¢r*åöBbßZU£<.U>ouªýª+£ðr*Bã¢+rCðUU_ÖÃ>îö>r©v:U_v@vCÜ>', SQLSRV_PARAM_IN, null, null), array('ývC|ãv:<BCÄrhÄÐ<ÖÄAChü£ÜB~|Bbb*:ßb|>|öbÃAÖ~oÐA,~ßß,b:+ba¢+<vÄ+,aÜv/BhUîß£+b.,>AbOýzvÄvý/a_är<ßüÃ,~,<¢Z/_î|/bv£¢C*.bÄABz@výb¢.Ä*ä,.AßC|/î~Ü,hãðo+îÐäbÄv£vUäO£rßhOu|Äå¢ßaÜýohvÄOÃ+ãÐÄ©ävoCÖÖ|v+ßÄÖ¢rîvuvÐBÐbb_C+~bOãß<C>zö.~CåUÐ.O<.©>v.h|ýªöÜO¢ý,o>B_ýu£hîüå£oääý©îbÐÄBßßÄU>@||Öß~ÐÄÜ£åruÖ_ªz@>.CÖöBZ_vÃb©zãÐÖC~*rBb/hB+ßÄäüO>@vrä+Üª:<Uzb£BzA<>£öUB//üý©~zª©Z>~ob£ä,+ýîÖãÖ,@ýAÖu,*UU.ãÜå,BaCäªa|bðv.ªBvö+*ªÃª<©||+ZöU*ÖräüýUüÜåUÐß<bÖ*£vbZåbUBZð:<~@ZbzÄob_¢Caý~<*öÜýªßuÐAåö<ÃöÜÃbß>r,öÃ@ÜîÃßaobZzªîh@ðãÃ*Üvo+U>CÜöAO>,b.vvuÄB/Ðäã©hBA£ohýßä¢Ã@h_/|AbCåßð_@åuÃh~ðb.*ÄB,BCÜîrÃ@<ÄÜCÄZ|.©Öüãr|_Üð:ãuö|vo:/A<îZu©h/bßCBýäA@.©,äåß|+ã:+:|î:ªÃvUB>,_ZÄß>Ð£ß<ýr/o¢Ü~|rî_:ÜZÃäo+@/ÄªöAACbhbzãÄaü,uhÐßßBÐbOöðÖaAö*,h.Ö,v.h>Ä:<åbß/ßî:Ð/_Ã*ý|ÖbB+bChý<~¢uaüÖªzC©UOCC>öB~uîã:bÖã@zbh¢z*î¢Uß~î>üãr~©Ö,£ü¢/ý|îb¢C|<o:/|O@+îZ@åÃ+OBUb£å.a<å+/Ä¢Ö/ßýCvCÖA|ZabzãBhBöbß<©ýüuo<Ä~bC>åª><@Ö.u:bÄß£ßbo,ÐU>£ßZÜßAîB©A:<o@OÐ+¢£+_ã,.ür*/BZ*ãß¢äbZÄ£<äz~.ªu¢C£*ßÖBîüß/åß¢Ã<a+Ä¢£|©B/£üo>rUýßÐ~uÜÖ<~Öî£b©.îAß,ZuÄýªb<|ðOÜð.Ö|:BbA<ÄvîCäÐîª:aÐö,CðAZßh@ÜrãZßß@î@rzãý*©Az~CäoäCî<ã|Zã@ovö_Ã>/ªzbuabuÃîöb<åÄÄÃv*îBöãýUrbýbbå<£OªÐvb¢oßîr|å@ãöß:ªÃOß.>Ü*ßaöbo£a>.vvý/¢£OCrÐvBÖ,COª<rväßCööU¢rÄÖ~_.B|©ßaüAa©OÜ@ub£ãå|Oßr|>oA>,Aü.:oÐÃ£ã:+~>oUOª*:ÄOü+ßO,ÄO|b>Ü_ªýAUÐ¢Ü_/ß,_üÃ©ö+îãªÜObªªvÜa>bÜö~bîouðäÐÃoÜOüZuÄbUOh©OÖ/ö:©<ãÖb+A:öîÜ>Ð/bBðª.å¢ZbbüUå¢Z.Z>uÜv,îöC/o_+zð~@ÜB~vhabß,/ÜÜß<.o_¢Zvöo©rü>ýÐbðÐo.ÖC_ÄCh>oüåöho*öÖ,ßÃ£Z|ª/,Z©a*Ð:Ãª,A*ä:.<ßBývÐbßhAüÄBA@BO_B*ã>>ßU:v_ÜZªîð.uãO~zÄz<@ü,A*£BvãßÜC~ýzvÖã:_Aä/bÜÃß©î¢r.a+ü@:uB¢/>:ßhßãU_îã<UÜ/©_ZU£ã/,uª+Buaßã_ÐãB|.£ööä~CýßÐoUðZýhü<ÜaCuOÜÐ¢', SQLSRV_PARAM_IN, null, null), array('29a27f4f-9e94-45a9-9110-812ef69ee37c', SQLSRV_PARAM_IN, null, null), array('4262-03-20 19:16:36.081', SQLSRV_PARAM_IN, null, null), array('2065-02-17 00:36:00', SQLSRV_PARAM_IN, null, null));
        case 3:
            return array(array(-1584712173, null, null, SQLSRV_SQLTYPE_INT), array(170, null, null, SQLSRV_SQLTYPE_TINYINT), array(25360, null, null, SQLSRV_SQLTYPE_SMALLINT), array(1352271629, null, null, SQLSRV_SQLTYPE_BIGINT), array(0, null, null, SQLSRV_SQLTYPE_BIT), array(0, null, null, SQLSRV_SQLTYPE_FLOAT), array(0, null, null, SQLSRV_SQLTYPE_REAL), array(0.3807, null, null, SQLSRV_SQLTYPE_DECIMAL(28, 4)), array(0.4393, null, null, SQLSRV_SQLTYPE_NUMERIC(32, 4)), array(0.8725, null, null, SQLSRV_SQLTYPE_MONEY), array(0.2057, null, null, SQLSRV_SQLTYPE_SMALLMONEY), array('ZÄßÃ|vbB/O<ouU*+ð>ýÖ~AABß©Ã@ÄÖßz~åz@ü.Ö<*~<B/Ob_ðð<öå<vUÃÃîîª|.Ã,oª+öã._Ö_.a~ðÃªªhªÜvã/Cªbßv>ãäßOÜÄv~Äªb_ör*bvÃÖýZZ<ö¢.|Ð>ÜåaCAîÃ¢ãßu/aå|@U*¢Bb*+bZr_.ã|,h_BöÄb.ðZ©//î_~v/ð/,bð¢/:@öãß+vÜv/båðöã:ã/z:£î<_ÐöC>.Ozrð©@rC~Bö,£o<:Ã*<bý_©¢Oß_b.Cåuß+BCäüv/£zvz,býÐýbAª£./£©ªÐäüoÐ:uÄªvb+ªÄbb:©U*uhßßb*bZª.ð:<UU/<<ªßBÃ<ÄÃ¢>z_ªöÜ,z,ªboB,+öCr*¢î*<£~ýb:U|©Bh/ãÜÖý:obhå£+Z+r:o|v+bÐhãåaüÐöbãðöAÃ|ªOCÖO|Ü<ãvv¢ãýbý.ÐbÄÃðåü>/BbbÄ/vÃ©äý:@o>öÃaªÐ+îüýã_röýä©zhvÜ<Ã/CäaðoCB|å~~ÖaðvuC_hBrOrzÃßO©ZU.AvvåÖÐ/ÐãåZ©£,UãÖAîhUzªrö£Ãu+ð/v¢o_<ÐA@', null, null, SQLSRV_SQLTYPE_CHAR(512)), array('ÄßZrð@~ö:ü:£,CoÄ©böBAO,ð:aA>ãÜBÐ@./:A.Z/bÖÜ,>ßî>ýßß©b/<@/,Öî>BBÃäÐCüÃÐÃvÜ_AZ.ý/©C_>aö/£Böða©£,öý£B_ÜÃðßvh|î|.oB/öBÜö¢BÐ/bAAÜÄa£.ªA©z<£ýOÐrå._bÄÜß~Ä_ªý,|+BÃ£îA~Cî@ü+@ÜüzCªr.rzåazUöCzBßª©Bö+ü*ZÃ£Ö@AC*UA¢..aÜü*ArÃz£B:ßßÄ+Ã/ãª+ßZ_Ü<ßäîýýî@ðÄÜßÃÖðova£ªOöÄzÖ©ãrabªÐUrår+Ü*©OöåBö|a©î:bß©ð~_C_o*hÃ@åBb|<åÄß@©ý.Ubª,O£Oz|üßbz£+bã¢a@>:aaîý_Ür£|hÃ@z<_hüÃü,öîZýuã_¢üå£<ðßAª>rC.Bî.©,ß*å|Ã©*_B>CÄîÖÃU~ÃÃ>rª>/ð©Ö|~ZA>¢¢/@£bZuZößzðå~:/h@uÐoOrã<¢aîßüß<¢BZzO¢@.:rvÜo>ABzC/ÜßÖ::r©O/v*@üaäzßZhU@aßvüî:©ü~ðª©_b£ä£ãB@:bhCÄZÜzOUßoåîÜý><', null, null, SQLSRV_SQLTYPE_VARCHAR(512)), array('Üarä+Öã/ür:h:AÐbðÃov¢©+ß*C/ß£Z£,ozz@UuÜ,ääßBÐB+hhãäz*rîC©/@u*bßÃ|býhÄrãÃîÜ/ÜäUbuÜv£ß:*bü.räßÐ_bßbC_©Cr:ðao/|ßhU.+v<b.aUuAaÐ>+aö+ÐªÄÖÄ,C|ãÄßvð>ovü:<:bý/båz£B>ö~ýrbãO/@ÄüoðrÃrüz_ã:@ð|@ZÐO©ä,Öabîhßbä©ýã<Ð>vhßÐäîaBUA/©:ÄÖBaÜ£zuhäz|.ð:åä¢bObuhö<ßö,£Öa<>**C|ýbß.UhZ<o:ý~u./,<,<¢o_üÄö~.ããbb~zzå_uÜßBä<Z+üß.+ªBaüå@.:BC<©Ð:¢B,.Uð@îhÃ~,ÖÃUªÐhOAUÐbBÄßð:/Bzh+/ãOý~~>ãÄü*,>ÖÄO@.*>ÃOåbvÃ/ýb<b,/a*ãb¢o|Ãr|ÜÄÄubuUðÄü©©UüCÄåh@Üuîöü>/äCîuÜß>¢C+|<*Üã|*.Ü*båöîv/O/*äÖÖÖ©+CA<räO¢ãÜh/ßÐöÃ~|Oh*ýOýü+¢bZUªßÐÃ/Ä_årðo<AoÖäª:>£Zr~ªz,ðA@ãuCzªO*ÄCzuoÄ*UüäöaUCÖO~£ö,uC,Aß>++Äbh/ý.o>Cv>ªäz¢:/ðß.BA_Ã©ßC~ÖZ:©*ý|ðÐ@AãövbZo+_î>ð¢äÄüvÃ@<ÜrZ~ÖããßvåÖÄÐîîö:ßöU@zvrBã©~ü+Äå/UÖã|,_U<*b£hßOÄüÖ_:Ðvö<ðb>îoUäîOåZ>ob.bZÄÜOu¢bZ£üªrãUohZZî<å¢br<O<v>Ä|î+<oa>ÜuzaOvr©©bðÐhOäoðOÃ¢~Äbhßßö.Äã+bh£@bª:Ä*å|ßC_|+@ÃÐ/Z<.ðbOou,zßößÄã~>Ãß~<>*ÖaîöªU::Ðü/bªÖÄ:å/uzAbªý.Äüäãåoößh£ðoU|ýöåbý.¢uãÜuðoUOz©.ð_îvª¢äß<v<BßZ£äu+ha¢ªÜ<CbªöýÄ£ãý@UÄ©Ä*vrOö+ÜÖýðZZAßo£ß*Ö:|Ð.ßZªhA,@*u£<Ö,ðbUBîOäBÖ/C|ªÃzzr¢rbA|ü_öß~vUîZ@rßÃ<ß,ß+åZbä<ª:ÃOAAAvÄ+,BÐhCª~ããC¢+ßýä>ãßãOÐ>Bb+*åbuåvu@<Z+buÃÜZ<rÖãu©Ä*_ru:_¢åa©ßÜÄ>¢üã,äzÃ>/ªCbåvZ*rCÃOÖÄÃbßýã/+/:ðbßäuî<,*aãCªB,£*ª©:©b@ÖîZß~å<vhahrab/b/ÖÖ+Oîªbü/*@ãÃüÜã_bööCå>äÐÄB:å+~ð_uÄðÄ*ãzrão|ü::A,+<a<Czåv@<r>¢bbÖªZ:UäCru£ÐZÐöåvvbb@å~:/ðã©rä£ßªÖBß,oýß@UvÜã,Aoz¢Oð/_|Oohå_/rî*ªãh_¢h>Cvv|v¢ßªbîÃ*\/@Ã~bßar|ýü¢åbîÐUðO£Üãî~+ã,oÜîö>+UbÄ+ÖðÜ£Äý@>üÐßabîCð¢Cª¢CuU.ã:©b>U¢ýO+>ý.abã.B,B|oÃýã|Öh<bU@Ã,ßvz<aÄBãßÄ.C@ªbÖuAãhãÃÖ©rð@>@CCüzArß_ðBÃýÐ*ýªÐbhbÐäuÃ__C_©UbO¢zÄ~/ß>.vuZðhoC*Öb>å,.ßUîZß@bväÃa>+.£.oð|Ðß>|Z_+.ÜÜ~Ürßu_:ÃCýýza+ªð©ßÐÄ+î|z£¢ZÃ¢Ü/Bz>vðßÃhäOÐAö|/ßCü+å~Ãb/ÃuîO¢uÖb+~åa@BÃhü£ä@¢äªÖußOÐUåä/UbªÄî@_£/©Ã,©.vAvuîöß|ßß¢hüßbAß>Ä<Ü:>ýu<BuBüOubß|h<aýîªÄvÜbÜ_Öª.ãr©r£ß_va:ãª:ã£,bouî|ß@ßäboÐî<ð*¢ÃCý~åoÖªOa_býU:hÄÄ,_ÃãbÄä*~£ß,_@>üÜÄ£ßOý<Z,ßüÐåßÄ~OÜ|ßöãbZÐ_~BÜß>A~a<Ö@î_.b.<UÐC', null, null, SQLSRV_SQLTYPE_VARCHAR('max')), array('55a1f242-dad9-4f8e-b839-364fb6e1ffec', null, null, SQLSRV_SQLTYPE_UNIQUEIDENTIFIER), array('7060-11-11 17:57:33.899', null, null, SQLSRV_SQLTYPE_DATETIME), array('1920-07-05 00:42:00', null, null, SQLSRV_SQLTYPE_SMALLDATETIME));
        case 4:
            return array(array(1640057440, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_INT), array(229, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_TINYINT), array(-13459, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_SMALLINT), array(-8557402, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIGINT), array(0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BIT), array(0, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_FLOAT), array(1, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_REAL), array(0.3122, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(28, 4)), array(0.3036, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_NUMERIC(32, 4)), array(0.8606, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_MONEY), array(0.4224, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_SMALLMONEY), array('uåîAZ©ÄZöäÜO@ÐðZ:r:vÜ@ýA/,O,ãBß>¢hð.Z>£Bßª+¢ªZU/@@äB.orÐîå¢ð>*<äAv~,ß@ýü~+<oo+/hCÜåzuÐb:ðr@.åÄ¢<h>~*Ðå¢ý_å_bÄb~_<<ßÄ:o¢zrC<ªa~BäÐýA©ßB.Uhîß+rAÖå¢ýö.îîaýåUC¢/Aüöß©zÄaðÐ,b|||+vCO+~üA£ÄöãýbÜ_üßãCðã|_ÄbäÃU~***<¢¢höUãbözbÄ>Ðühr.vÐ:£_Ä~<ZÐvÜb+¢>@/o,a_abý_>ßr*å|bob¢îãBî~<üBÜouÄBar_üß.ÐÐ,©ýuÖUöäÐÐîZýªÃ*vß|~îZßZÜÐrh*~UÜ*î@OBÃßraUb:*/B/@OÄaãoßBãÃ<AAß@o>höBb@Uªý*|£U+ü*¢ß¢häUðOb/*.rßOrÖåüO<ý*aöCa@ªoä>ÐC.UO+ZUrÜA©Oã,ro+î+,Üå¢<¢rÐu.ªî|ãÃB,ða,ªÐüÄü©ßBÖß+uv>BöußÜ|h|aßohB*ovãu+@Ü£ßO©ßßBý:b+£bÐÖäªo:UAÐo_ã~>ö<ühåÖÐ<hbCýÜA~|Üß', SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_CHAR(512)), array('Aª./UbîvÐð££ãuzo@>î>å*v¢ß<OÃr<o.Ð*_å|bÄbü>a_>/ZÖ:åbbäz¢Ä*Ü¢ÜåubvÖUî@Ã:¢<Üß_.*Öh,o/uz.B_/Äã|Ü/öOÐ.ÐßU@ßbav~zAßu+ª£U¢<ýÖä©Ä>ßãåäbã¢ßýªöåä,*ubßß@¢><üCozÐÐ©äC_aauC/_<.ýuÜ£Ö,uCÜÃbåräZ,ðÐî@îbzÖã+ã,CB£ZzB¢vÄ*+Üb¢üýßU*oÄärÃ£ü@öîaß.|äý©bÖ|BuA©ª,C/ZB*ð~aÃÃvîü©+ªÃ+Ã_öuu¢ZöbÄuð©O¢Z@_uä|bu,äOÐÜbBr@|Ãüb/îr©ß.ååßÜabZ©hß+ãßªz|+Aå@äü>Ä+ýu|å¢z|bhr*ªbO©>/ö,hÐå+Öå_OßZ|ð,b.AäÐß_@ßß©.üüZäuA/aC|£CäßýbhÖÖªZö@ÃhßÖ£/å*örüßðU*~vhðv_Üðýðß<+bAbBa:ªÄ/vÜªUuÄîîabãßO>:,Ãýðußßäö@vîhäÜ>£¢hý+zAZbaBðÐ£|å|ªÐ*:Ã>ª:ð£ÐüßÖbuªOOA>Bb©ÃärÐîhzö,+|C:Aö', SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(512)), array('|:ÃZ*UvAÃC:+ãb@ÃÃÖãZä|Üzß~:rðß~ub>ö£+++ªðÃuaå~|zA:ÜvCãÃa©,@ÖßbCå|rz+Cåzz,U+OzÜ:rAhO<aZCußðüýã¢Üä|*:©vrOhh:åå>@ÃîözÐÃî©åvBb|Oî*<Ü<@¢ýzÜðßöAz|.UÖZUbb_åBüî<O~r£ýußýB.ýåÃ~o.îÖä*zäã*Uöãvöî,_Ä_hrÐOüß@~zãÜ.|@åhü*C~h*Ðä.A._ArA:Oo/ö©ÐÜbßÐ_ý~ã.*.ßðA/h,ß<äßð.ý_Cßzu,bö<U|bÖ/ßÐv|£bhuvaaAß¢<ßöä©ãr/Ov¢AU:*Ã<,.üAÖªuZ*ãZäßZOª:<©ZÄ.îu+|aö¢ßÃî~_££ßªÐrÄ~*aî~,ü//ßüÃ.Üü>Ö©O~Z©@o.vv£|ÜäÖ:~CbîzÜoA_OaÄuOÜÃobð_¢/îßã@zC*ä£öuuß£îª/îa_Ü>@:OB>ü.ÄörßorUbß<bÄ©|b>ý,hÜ|£A@b©/z.*u¢+<öaA.£/©_¢/üãBö:üOÄ+OãbîÃÐ@ßð©>U/+r¢b<ªÖÄCAaª|U|>å:o@vo£äB*©C£ÐåhO_>î*.@~ü£bßbzh>üzbr*ÐhO|ÖöCð¢b,oå.AÜ£o>ö~_£BãßßOOo*©©ß:,Z*ª:,£+|Ã£oîCÄzo~vÖO@bÐðoðÖ:ÖÐ>bü£<z~A|ðOb.O©åb>äOB.bUåu¢©Z:îîßüoBö<ua~|Äãa/Ã_vvävª~¢baÄÜUÃîÐCaZv:A~öªUäª_ÃuU:_b.Aª£ZU*bbUÄýzÃßªßÄhu@Ä*UaÖýB.ß:zÄª@Ä_,ýABö@ÃîU|îÐB/_Zz~ÄÄ*¢|vUÃvß,vÄOCb/ÐCh|r£+Örb,Z+îa.AßýOÖ:Üävuå@ýãÐªAýßOoA@býä*Oå,ðî+å©,CåÃv*vvüÖãÜ+*Üv,bãuýZöråB~ýîzýoCo+<oz_ãîÖ_ZobýäBC|ß_£ÃbO©ßOÖZ,©Ü©©OüzBb_ý*UÜ£AßCÐåÜÜo£<>ZZ>_z©<C_ü©å£CbbZ+©åða~zößCb@¢/zBýªüÖ£b+£ÜUhãî.aÄå+@+B£oaå@@|/ªð|+ß©_bz£ZzUhÖ:>A/B*£vîöý*/öåC|ßro*~<CÖ>¢|Ärbä@/~,>b:bü££/ßzCC:_Ö@Aü.åAðÃªÐb|Äîhãa,>£>*hrÃðbZ/,@CO<_ß_AÃ+|.ä+îbö_Ã<CÐüåUoîÜÄöü¢Uurö,U+zZýÃCbb>ÜZªý_¢övÜhZr,ðÜzÖ,öh¢höÄÄo+OCAãßO©C<Bö:ýZü:ßbAý,_©öÖhðå£*îZ+vÜhÐz@AÄßZrßööã©Äh¢©Oý©<BB©Uß>hr©bÐ_ßÄ>~av~u:hßrBrörv£OvC©£uãÜî*oahärzãý©b©î./aüv:o~|öðAî:/hbU', SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('max')), array('34eeff6c-7d28-4323-9e28-d6b499fde336', SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_UNIQUEIDENTIFIER), array('4191-02-05 02:41:51.953', SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DATETIME), array('1975-12-01 15:24:00', SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_SMALLDATETIME));
        default:
            return array();
    }
}

echo "\nTest begins...\n";
try {
    explicitFetch();
} catch (Exception $e) {
    echo $e->getMessage();
}
echo "\nDone\n";

?>
--EXPECT--
﻿
Test begins...
Comparing data in row 1
Comparing data in row 2
Comparing data in row 3
Comparing data in row 4

Done
