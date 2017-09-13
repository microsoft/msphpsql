--TEST--
Test a complex query with IDENTITY_INSERT
--FILE--
﻿<?php
include 'MsCommon.inc';

function ComplexQuery()
{
    set_time_limit(0);  
    sqlsrv_configure('WarningsReturnAsErrors', 1);  
    
    // Connect
    $conn = Connect(array("CharacterSet"=>"UTF-8"));
    if( !$conn ) { FatalError("Could not connect.\n"); }

    $tableName = GetTempTableName();
    
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int IDENTITY, [c2_tinyint] tinyint, [c3_smallint] smallint, [c4_bigint] bigint, [c5_varchar] varchar(512))");  
    sqlsrv_free_stmt($stmt);   
    
    $noExpectedRows = 0;
    $noActualRows = 0;
    $stmt = sqlsrv_query($conn, "SET IDENTITY_INSERT $tableName ON;");    
    sqlsrv_free_stmt($stmt);   
    $noExpectedRows++;
    
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_varchar) VALUES (1324944463, 105, 18521, 2022363960, 'üv£ª©*@*rãCaC|/ä*,,@ý©bvªäîCUBão_+ßZhUªî¢~ÖÜ/ª@ä+ßßar~Özr,aß/,bCaü<ÖÐhÐbß<î/ðzãý+bÜ:Zßöüª@BÖUß<U@¢Ö<hÖhubÄrÐÃ*.å|a/,ª¢ßOa@oubýãýý£îZ~,ä¢î|+ª¢rZUCrOu,B£åß|:£ªîoBärÐA/BzOoß<bvu~ßuîCãß¢¢îýA@aðuAa,UÐ.>Ußaåab|¢ª¢|ü£/ÃßzzuªãA.ªZUöß<©a>OzübBüÜ|bZ./öbvß*rbö>ß©r//~ÖCÜhð¢bßz:¢Ä+_Ã__£ý£Uýh:v¢bý,©Ü£©,A:Zh>ßåvö+Ä>Ã.ßvC|:Ü*Üü*åz|b.©©üAý@uU.oOÜýAÜÐÜð©OB|rrîbU<övoUßäZÃÖ<ÄåªAÄ.Ua*ÖOªB,åîzB:ÜhövÖZhýðüC')");    
    sqlsrv_free_stmt($stmt);   
    
    $stmt = sqlsrv_query($conn, "SET IDENTITY_INSERT $tableName OFF;");   
    sqlsrv_free_stmt($stmt);   
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_varchar) VALUES (-1554807493, 253, -12809, -1698802997, 'ßöîÄ@v+oß+î|ruý*Ü¢ãÖ~.*ArABªßOBzUU£ßUuÜ<ðýr|b~_äaü/OÖzv.¢ä>>OÜ+¢vªzöªoB_ä+ßªrÜö£>U~ãÖð~ðýur,ÖvZh¢ªZ>vªUäåîz,>ÃräðäýðO_ä*a,ö+üÐß~bÃü¢<<+îýÐöäªO/zA+:îZ,öBÐü<î£îåhBÖzßÄ~,:>bð<~aÐãö¢*¢våvÃÐåî@a<vBãßÖäåª¢<üa.u:>_Äu£öa~våu>¢Bã©å:Aßã£Üvåö+aä£U<bUu*rv+@U_|ð@+v@Üßb|,.ªäÖ/*ÃªýÄ¢¢Ö/+ä><¢b@|zbãÖ@ÃãUb|ÄB£©,~ßð©ðUßöZÜöî£Zz<>åäZßð©ßaÖÖ¢bð£ßÄ>îÃÃ.~z>h_ý~ÜrüÖBruß+ª©CB©O>rå,Chro,£ßbZ_ß©,ÃUu|ßåüÄ/ý*åu|~Ö.ßZUoä:~A~CZhðU|öZ:ä/£Ä*î©ÄhävhbrzîÐ@.rãß©@uÜ©~>ÖÜööCÄzÜCü+>oZÄÜ/ABßA_b|b¢bÜh<|uBr.B*rü>aCª|AÄ©@öÖßÖ~Ö<rÐ,ä@©|££.C.üå¢/rbªßî')");    
    
    $errors = sqlsrv_errors(SQLSRV_ERR_ALL);    
    $count1 = count($errors);   
    $e = $errors[0];    
    $value1 = $e['message'];    
    print "$value1\n";  
    $value2 = $e['code'];   
    print "$value2\n";  
    $value3 = $e['SQLSTATE'];   
    print "$value3\n";  
    $noExpectedRows++;
    
    $stmt = sqlsrv_query($conn, "SET IDENTITY_INSERT $tableName ON;INSERT INTO $tableName (c1_int, c2_tinyint, c3_smallint, c4_bigint, c5_varchar) VALUES (1994559593, 129, -8455, -236232445, 'ß£*ÐO+ö+<ã:>üoîzÄ¢zz~Ãýö|vUå>|CÄUü~>buÃv<ä~Ö+.ü*ªbuî_bBC©.oîCbåîÐÖUa~/U>öAäÐBu~ozîZ/zrOOä:ß©bßo.ü©A¢höÖoßÖü>r+A/ßaªrß:ª@|bhhªª/oå<Ö:rüa+oC¢~uÄü>/.ãbOöª_b@bbß¢|uzßªÖ¢~uäýub©ãaZäC£ÄrÖ,üöäu+Ãîö|||,U.BråãoýbüåöÃburöoî+>öä©î,u_öb©@C:ÜåÜîÜåAÖzýbð|Z<Ãý.£rîZ|/z@¢£AýZ,ßuZ*:b.AzÐ¢ä¢üßöbvbväð|<**~Uv.Ð*Ä©B*ýCUöa¢åO©Ãß*ÃÃ|ÜðA@îÃßaB<hÜîaZoöå>öüahUUA+ß£_u|~äö.©hr£oBo<äãüO+_å<OÐªÖßßväzA,~u~Obbî@ßÃãÜää©,.bO:C£Ü,äUO¢åå**hÐ~UZ©ðh<abß*üÖîC.äßh~Uð<r*ßäv£î*@¢Cv/BÖhAüB~ýAå@Z@<a_O|<©ßb*CZO,o:ã+¢£ÃZC©B¢o+>O:Z~Üoîßzb£ª£A.AÖÜÄ._O_å£ß');SET IDENTITY_INSERT $tableName OFF;"); 
    sqlsrv_free_stmt($stmt);   
    
    echo "Number of rows inserted: $noExpectedRows\n";
    
    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");  
    while ($result = sqlsrv_fetch($stmt))
    {
        $noActualRows++;
    }
    sqlsrv_free_stmt($stmt);   
    
    echo "Number of rows fetched: $noActualRows\n";
    
    if ($noActualRows != $noExpectedRows)
    {
        echo("Number of rows does not match expected value\n");
    }
    sqlsrv_close($conn);   
        
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    StartTest("sqlsrv_statement_complex_query");
    echo "\nTest begins...\n";

    try
    {
        ComplexQuery();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("sqlsrv_statement_complex_query");
}

Repro();

?>
--EXPECTREGEX--
﻿
Test begins\.\.\.
\[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Cannot insert explicit value for identity column in table '.+' when IDENTITY_INSERT is set to OFF\.
544
23000
Number of rows inserted: 2
Number of rows fetched: 2

Done
Test \"sqlsrv_statement_complex_query\" completed successfully\.
