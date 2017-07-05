--TEST--
Complex Query Test
--DESCRIPTION--
Verifies the behavior of INSERT queries with and without the IDENTITY flag set.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

include 'MsCommon.inc';

function ComplexQuery()
{
    include 'MsSetup.inc';

    $testName = "Statement - Complex Query";
    StartTest($testName);

    Setup();
    $conn1 = Connect();

    $dataTypes = "[c1_int] int IDENTITY, [c2_tinyint] tinyint, [c3_smallint] smallint, [c4_bigint] bigint, [c5_varchar] varchar(512)";
    CreateTableEx($conn1, $tableName, $dataTypes);

    Execute($conn1, true, "SET IDENTITY_INSERT [$tableName] ON;");
    Execute($conn1, true, "INSERT INTO [$tableName] (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_varchar) VALUES (-204401468, 168, 4787, 1583186637, 'î<ÄäC~zããa.Oa._ß£*©<u_ßßCÃoa äãäĞßa+OühäobUa:zB_CÖ@~UÄz+ÃîĞ//Z@üo_:r,o¢ÃrßzoZß*ßªªå~ U¢a>£ÃZUÄ/ä_ZãğäåhüCã+/.obî|ößß,ğ¢ğğ:ÄĞ:*/>+/¢aö.öÄ<ğ:>äO~*~ßÄzå¢<ª£ğı.O,>Ü,åbü@böhıC*<<hbÖä*o©¢h¢Ğüa+A/_@b/ÃBıBªß@ã~zÖZıC@äU_ßUßhvU*a@ÃğÄ:ªZAßAb£U_¢ßbãä:üåãorıÃßª_ãĞÖªzãğåãoaü <ß~zZªaB.+åA¢ãÖ><î:/Ur î¢UßåOaÄ:a|++ª©.r~:/+ä|©ıo++v_@BZ:©©AßCğ.©/Ab<,îß>UãÜÜöbb|ßĞß£:î<<bîöa+,<_aÄ._ª>Ü<|ÖzÃz@>¢ª:a,CÜr__ª.<öÜCã+UÖU¢_üzü bÃ~ßo|, .î,b/U>äıaBZ@Ü£: bÖvıb>Ã/ÜÃ@üÖ/äb¢+r:Zß>ĞÜ|üu©ßZAC:Cßh *.Ã££_ıîu|Urå.:aAUv@u>@<Öü.<ãZ böZAÜÖ£oüĞä*,ü:ğä')");
    Execute($conn1, true, "SET IDENTITY_INSERT [$tableName] OFF;"); 
    Execute($conn1, false,"INSERT INTO [$tableName] (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_varchar) VALUES (1264768176, 111, 23449, 1421472052, 'uå©C@bğUOv~,©v,BZÜ*oh>zb_åĞä<@*OOå_Ö<ãuß/oßr <ğãbÜUßÜÃÖÄ~¢~£ bÜ©î.uÜĞ¢ª:|_ĞüÄBĞbüåßÃv@,<CßOäv~:+,CZîvhC/oßUuößa<å>©/Ub,+AĞ©î:ÖrıB+~~ßßßãÜ+_<vO@ ßÃüÖîaCzĞîå@:rı.~vh~r.ÃbÃã©å_îCär BÖÜ:BbUväåöZ+|,CîaAöC,aîbb*UÜßßA hCu¢hOb ğ|ßC.<C<.aBßvuÃÖå,AĞa>ABğöU/O<ÖãüªOãuß£~uÖ+ßÄrbî/:ÖÖo  /_ÃO:uÃzğUvã£Aã_BĞ/>UCr,Äå aÄĞaÃ£vÖZ@ªr*_::~/+.å~ğ©aÄßbz*z<~î©ªrU~O+Z|A<_Büß©¢ö ::.Übıüßr/örh¢:ääU äOA~Aîr<¢äv¢Ä+hC/vßoUª+Oãªã*ğ¢Bö.Zbh/ä,åä>*öğßUßı>aªbBbvßãÖ/bã|ıÖ u.zı©~äğzĞU.UA*a*.¢>î rß ~Cüßaö+rª~ß@aã/ĞCß*a,ªÄbb<o+v.åu<£B<îBZßåu£/_>*~')");
    Execute($conn1, true, "SET IDENTITY_INSERT [$tableName] ON;INSERT INTO [$tableName] (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_varchar) VALUES (-411114769, 198, 1378, 140345831, 'Ü@ßaörÃªA*ĞüßA>_hOüv@|h~O<¢+*ÃĞCbazÜaåZ/Öö:ıãuöĞaz£ĞAh+u+rß:| U*¢ªåßÄĞ_vî@@~ChĞö_å*AAıBö¢B,ßbßå.ÃB+u*CAvÜ,ã>ªßCU<åî©ürz¢@ör¢*Öub¢BåaÜ@ª.äBv¢o~ ßıo oîu/>ÜĞÄ,ğ,ğaOÖå>ğC:öZ>ßåğ©<ğ¢+£r.bO.©,uAßr><ov:,ÄßîåÃ+å./||CUÜÜ_ÖÄªh~<ã_å/hbı Ä©uBuß<Ö@boÖıBãCÜA/öÄ:© ßUü*ıvuß.Bãååo_übır_üß>ĞÃÜ£B¢AªvaîvıßCÜUß  åvöuª><îĞUC*aÖU©rªhr+>|äıî|oğröĞ£<ª<Ö|AªohäAî_vu~:~£Ãhü+ÃBuÄğ ü@Z+Ä@hÖî¢|@bU£_ü/£ |:¢zb>@Uß©  Ãão Ö@ãĞBã_öBOBÄĞhCÜb~Ö>îü rıåüUzuãrbzß/ªîUĞğ©uå.ß@£__vBb©/Ür¢Öuåz£ä*å£/*ÃO');SET IDENTITY_INSERT [$tableName] OFF;"); 

    $stmt1 = SelectFromTable($conn1, $tableName);
    $rowCount = RowCount($stmt1);
    sqlsrv_free_stmt($stmt1);
    
    if ($rowCount != 2)
    {
        die("Table $tableName has $rowCount rows instead of 2.");
    }

    DropTable($conn1, $tableName);  
    
    sqlsrv_close($conn1);
    
    EndTest($testName);
}

function Execute($conn, $expectedOutcome, $query)
{
    Trace("Executing query ".ltrim(substr($query, 0, 40))." ... ");
    $stmt = ExecuteQueryEx($conn, $query, true);
    if ($stmt === false)
    {
        Trace("failed.\n");
        $actualOutcome = false;
    }
    else
    {
        Trace("succeeded.\n");
        sqlsrv_free_stmt($stmt);
        $actualOutcome = true;
    }
    if ($actualOutcome != $expectedOutcome)
    {
        die("Unexpected query execution outcome.");
    }
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    try
    {
        ComplexQuery();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "Statement - Complex Query" completed successfully.
