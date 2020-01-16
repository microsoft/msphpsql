<?php
include("MsSetup.inc");

$tableName = "aev2test";

// Array of possible ColumnEncryption values. $ceValues is for the initial
// connection, $targetCeValues is for reconnection. The array keys are to be
// passed to testCompare and testPatternMatch so we don't have to pass the
// actual attestation info, which resides in MsSetup.inc.
// For incorrect protocol and attestation URL, insert a rogue 'x' on either side
// of the comma.
$comma = strpos($attestation, ',');
$wrongProtocol = substr_replace($attestation, 'x', $comma, 0);
$wrongAttestation = substr_replace($attestation, 'x', $comma+1, 0);

$ceValues = array('correct' =>$attestation,
                  'enabled' =>'enabled',
                  'disabled'=>'disabled',
                  'invalid' =>$wrongProtocol,
                  'wrongurl'=>$wrongAttestation,
                  );

$targetCeValues = array('correct' =>$attestation,
                        'enabled' =>'enabled',
                        'disabled'=>'disabled',
                        'invalid' =>$wrongProtocol,
                        'wrongurl'=>$wrongAttestation,
                        );

// Names of the encryption keys, depending on whether we are using Windows
// or AKV authentication (defined in MsSetup.inc). -enclave keys are enclave
// enabled, -noenclave keys are not enclave enabled.
// $targetKeys are the keys used for re-encrypting encrypted columns
if ($keystore == 'win') {
    $keys = array("CEK-win-enclave",
                  "CEK-win-noenclave"
                  );
    $targetKeys = array("CEK-win-enclave",
                        "CEK-win-noenclave",
                        "CEK-win-enclave2",
                        "CEK-win-noenclave2"
                        );
} elseif ($keystore == 'akv') {
    $keys = array("CEK-akv-enclave",
                  "CEK-akv-noenclave"
                  );
    $targetKeys = array("CEK-akv-enclave",
                        "CEK-akv-noenclave",
                        "CEK-akv-enclave2",
                        "CEK-akv-noenclave2"
                        );
} else {
    die("No keystore specified! Aborting...\n");
}

// $targetTypes are the encryption types used for re-encrypting encrypted columns
$encryptionTypes = array("Deterministic",
                         "Randomized",
                         );
$targetTypes = array("Deterministic",
                     "Randomized",
                     );


// Length of the string-type columns. $slength is length as a string instead of integer
$length = 64;
$slength = '64';

// Testing the following data types, split into two arrays because if we try one array,
// at some point we get a CE405 error for no clear reason (might be a memory issue?).
// TODO: Follow up and see if we can use a single type array.
$dataTypes1 = array('integer',
                    'bigint',
                    'smallint',
                    'tinyint',
                    'bit',
                    'float',
                    'real',
                    'numeric',
                    'date',
                    'time',
                    'datetime',
                    'datetime2',
                    'datetimeoffset',
                    'smalldatetime',
                    );

$dataTypes2 = array('char',
                    'nchar',
                    'varchar',
                    'nvarchar',
                    'varchar(max)',
                    'nvarchar(max)',
                    'binary',
                    'varbinary',
                    'varbinary(max)',
                    );

// Construct the array of column names. Two columns for each data type,
// one encrypted (suffixed _AE) and one not encrypted.
$colNames1 = array();
$colNamesAE1 = array();
$colNames2 = array();
$colNamesAE2 = array();

foreach ($dataTypes1 as $type) {
    $column = str_replace(array("(", ",", ")"), array("_", "_", ""), $type);
    $colNames1[$type] = "c_".$column;
    $colNamesAE1[$type] = "c_".$column."_AE";
}

foreach ($dataTypes2 as $type) {
    $column = str_replace(array("(", ",", ")"), array("_", "_", ""), $type);
    $colNames2[$type] = "c_".$column;
    $colNamesAE2[$type] = "c_".$column."_AE";
}

// The test data below is a mixture of random data and edge cases
$testValues = array();

// integers
$testValues['integer'] = array(0,-1,1,2147483647,-2147483648,65536,-100000000,128,9);
$testValues['bigint'] = array(9223372036854775807,-40,0,1,2147483647,-2147483648,65536,-100000000000000);
$testValues['smallint'] = array(4,-4,-32768,-99,32767,-30000,-12,-1);
$testValues['tinyint'] = array(2,0,255,254,99,101,100,32);
$testValues['bit'] = array(1,1,0,0,0,0,1,0);

// floating point
$testValues['float'] = array(3.14159,2.3e+12,-2.3e+12,2.23e-308,1,-1.79e+308,892.3098234234001,1.2);
$testValues['real'] = array(3.14159,2.3e+12,-2.3e+12,1.18e-38,1,-3.4e+38,892.3098234234001,1.2);
$testValues['numeric'] = array(-3.14159,1.003456789,45.6789,0.0001,987987.12345,-987987.12345,100000000000,-100000000000);

// dates and times
$testValues['date'] = array('2010-01-31','0485-03-31','7825-07-23','9999-12-31','1956-02-27','2018-09-01','5401-11-02','1031-10-04');
$testValues['time'] = array('12:40:40','08:14:54.3096','23:59:59.9999','01:00:34.0101','21:45:45.4545','00:23:45.6','17:48:00.0000','20:31:49.0001');
$testValues['datetime2'] = array('9801-01-29 11:45:23.5092856','2384-12-31 12:40:12.5434323','1984-09-25 10:40:20.0909111','9999-12-31 23:59:59.999999',
                                 '1259-04-29 23:59:59.9999999','1748-09-21 17:48:54.723','3125-05-31 05:00:32.4','0001-01-01 00:00:00');
$testValues['datetimeoffset'] = array('9801-01-29 11:45:23.5092856-12:45','0001-01-01 00:00:00-02:30','1984-09-25 10:40:20.0909111+03:00','1748-09-21 17:48:54.723-09:21',
                                      '4896-05-18 23:17:58.3-02:00','1657-08-04 18:14:27.4','2022-03-17 07:31:45.890342+09:30','1987-10-25 14:27:34.6320945-06:00');
$testValues['datetime'] = array('9801-01-29 11:45:23.509','2384-12-31 12:40:12.543','1984-09-25 10:40:20.090','9999-12-31 23:59:59.997',
                                '2753-04-29 23:59:59.997','1948-09-21 17:48:54.723','3125-05-31 05:00:32.4','2001-01-01 00:00:00');
$testValues['smalldatetime'] = array('1998-06-13 04:00:00','1985-03-31 12:40:00','2025-07-23 05:00:00','1999-12-31 00:00:00',
                                     '1956-02-27 23:59:00','2018-09-01 14:35:00','2079-06-06 23:59:00','1931-10-04 19:52:00');

// strings, ascii and unicode
$testValues['char'] = array('wvyxz', 'tposw', '%c@kj>5', 'fd4$_w@q^@!coe$7', 'abcd', 'ev72#x*fv=u$', '4rfg3sw', 'voi%###i<@@');
$testValues['nchar'] = array('⽧㘎ⷅ㪋','af㋮ᶄḉㇼ៌ӗඣ','ኁ㵮ഖᅥ㪮ኸ⮊ߒᙵꇕ⯐គꉟफ़⻦ꈔꇼŞ','ꐷꬕ','㐯㩧㖃⺵㴰ڇལᧆ겴ꕕ겑וֹꔄ若㌉ᒵȅ㗉ꗅᡉ','ʭḪぅᾔᎀ㍏겶ꅫၞ㴉ᴳ㜞҂','','בּŬḛʼꃺꌖ㓵ꗛ᧽ഭწ社⾯㬄౧ຸฬ㐯ꋛ㗾');
$testValues['varchar'] = array('gop093','*#$@@)%*$@!%','cio4*3do*$','zzz$a#l','       ','v#x%n!k&r@p$f^','6$gt?je#~','0x3dK#?');
$testValues['nvarchar'] = array('ᾁẴ㔮㖖ୱܝ㐗㴴៸ழ᷂ᵄ葉អ㺓節','ӕᏵ൴ꔓὀ⾼','Ὡ','璉ǅꖭ갪ụ⺭','Ӿϰᇬ㭡㇑ᵈᔆ⽹hᙎ՞ꦣ㧼ለͭ','Ĕ㬚㔈♠既','ꁈ  ݫ','ꍆફⷌ㏽̗ૣܯ￠⽳㌭ゴᔓᅄѓⷉꘊⶮᏏᴗஈԋ≡ㄊହꂈ꓂ꑽრꖾŞ⽉걹ꩰോఫ㒧㒾㑷藍㵀ဲ更ꧥ');
$testValues['varchar(max)'] = array('Q0H4@4E%v+ 3*Trx#>*r86-&d$VgjZ','AjEvVABur(A&Q@eG,A$3u"xAzl','z#dFd4z',
                                    '9Dvsg9B?7oktB@|OIqy<\K^\e|*7Y&yH31E-<.hQ:)g Jl`MQV>rdOhjG;B4wQ(WR[`l(pELt0FYu._T3+8tns!}Nq<i-Nqbu@]1<K{PS[SHSF(]G[G XPLlAUezBm^&qn^mK(&]ss6&yVxW_N_J5V*iKcgXyb+Hz:HS<9>rc1%n@|N|ik C@ 03a/ +H9mBq',
                                    'SSs$Ie*{:D4;S]',' ','<\K^\e|*7Y&yH31E-<.hQ:','@Kg1Z6XTOgbt?CEJ|M^rkR_L4{1?l<e`N@');
$testValues['nvarchar(max)'] = array('ｘᐕᛙᘡ','ퟅ㚶Ἢœäᑐï','ꐾɔᡧ㝚ஒŪᚔᘘښ곅սꕟքꀉᎠኇ','t9p4r5', '﹨ퟱꈽᕧු꧍ۡᢙⴖ㒘ᆾ겇ᅞ〱㝸㛾㕥গଜ㳸ꍍ匿ཋ㵔ﬠᄩ᧙ꖍᕿ㩴ఽᙿ','ⴠ⿃ᶺ͚ᎉ઄㵨጑㛢㌋㙤ᙘّᘷ㬡',
                                     'ᵄご︵ࣲꌤꏵퟰꖛᏠƢᵙꌵ㙈㜂琢㎯㪏㐵꒚㧶ᐁቴƯɋü㶌領㻡㉉걂ꈊㇷѼμꅲڧᶀƸڍ⭩㉩㛜ꆶ㕸ꁺꖁ㓫ޘ갧ᛄ㶋㘚ᐋꗡͭచ㖔፟ꐸ㱯ⵜᥰꃷᇂὥ㗍㚀ꀊጿἢઔܛ᎓Ե⅜㛵጑ྣᏝ༱⮢ΫÊ㕮⽹','繁Ɇʓӿꩭਸꆟꑇ㳋Ήᴝ㕨㰵ꇳ');

// binary
$testValues['binary'] = array('3AD2BBC2', '7201', 'EED3A109F8F7745C', 'D6C3E0E11A25F3','4EACCEF38788F9','FFFFFFFFFFFFFFFF', '6230974598','44EE4A');
$testValues['varbinary'] = array('583412','F3ED38AAC3CDC87759DE34B23C223CCDAB42109FBC8889','E43004FF', '000005ED309D3A45','06CADEF379','8041','00000000000000','D7209FFE44');
$testValues['varbinary(max)'] = array('EF409CB33408', 'D3EA762C78FC', '00','FFFFFFFFFFFFFFFFFFFFFFFFFF',
                                      '582D40EF3EB4E9762C5AA49D4E40C42CB4009ED3E75F890A2FD14BF495EFF5378A23BB782C4A40E1D0005DA3FE208A48C1FE',
                                      '92300943891097','DDD4D88C4B80089D2E4A','88F8A8');

// The comparison operators to test
$comparisons = array('=', '<', '>', '<=', '>=', '<>', '!<', '!>');

// Thresholds against which to use the comparison operators
$thresholds = array('integer'           => 0,
                    'bigint'            => 0,
                    'smallint'          => 1000,
                    'tinyint'           => 100,
                    'bit'               => 0,
                    'float'             => 1.2,
                    'real'              => -1.2,
                    'numeric'           => 45.6789,
                    'char'              => 'rstuv',
                    'nchar'             => '㊃ᾞਲ㨴꧶ꁚꅍ',
                    'varchar'           => '6$gt?je#~',
                    'nvarchar'          => 'ӕᏵ൴ꔓὀ⾼',
                    'varchar(max)'      => 'hijkl',
                    'nvarchar(max)'     => 'ｘᐕᛙᘡ',
                    'binary'            => '44EE4A',
                    'varbinary'         => 'E43004FF',
                    'varbinary(max)'    => 'D3EA762C78FC',
                    'date'              => '2010-01-31',
                    'time'              => '21:45:45.4545',
                    'datetime'          => '3125-05-31 05:00:32.4',
                    'datetime2'         => '2384-12-31 12:40:12.5434323',
                    'datetimeoffset'    => '1984-09-25 10:40:20.0909111+03:00',
                    'smalldatetime'     => '1998-06-13 04:00:00',
                    );

// String patterns to test with LIKE
// For AE, LIKE only works with string types for now. Additional types
// are listed here because eventually the type conversions required for
// pattern matching non-string types should be supported.
$patterns = array('integer'           => array('8', '48', '123'),
                  'bigint'            => array('000','7', '65536'),
                  'smallint'          => array('4','768','abc'),
                  'tinyint'           => array('9','0','25'),
                  'bit'               => array('0','1','100'),
                  'float'             => array('14159','.','E+','2.3','308'),
                  'real'              => array('30','.','e-','2.3','38'),
                  'numeric'           => array('0','0000','12345','abc','.'),
                  'char'              => array('w','@','x*fv=u$','e3'),
                  'nchar'             => array('af㋮','㐯ꋛ㗾','ꦣ㧼ለͭ','123'),
                  'varchar'           => array('   ','a','#','@@)'),
                  'nvarchar'          => array(' ','Ӿϰᇬ㭡','璉ǅꖭ갪ụ⺭','更ꧥ','ꈔꇼŞ'),
                  'varchar(max)'      => array('A','|*7Y&','4z','@!@','AjE'),
                  'nvarchar(max)'     => array('t','㧶ᐁቴƯɋ','ᘷ㬡',' ','ꐾɔᡧ㝚'),
                  'binary'            => array('44EE4A'),
                  'varbinary'         => array('E43004FF'),
                  'varbinary(max)'    => array('D3EA762C78FC'),
                  'date'              => array('20','%','9-','04'),
                  'time'              => array('4545','.0','20:','12345',':'),
                  'datetime'          => array('997','12',':5','9999'),
                  'datetime2'         => array('3125-05-31 05:','.45','$f#','-29 ','0001'),
                  'datetimeoffset'    => array('+02','96',' ','5092856',':00'),
                  'smalldatetime'     => array('00','1999','abc',':','06'),
                  );
?>
