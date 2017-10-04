--TEST--
various fetch types.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

sqlsrv_configure('WarningsReturnAsErrors', 0);
sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

require_once("MsCommon.inc");

$conn = connect();
if (!$conn) {
    fatalError("connect failed");
}

$stmt = sqlsrv_query($conn, "SELECT * FROM [tracks];");
if ($stmt === false) {
    fatalError("sqlsrv_query failed");
}

$fetch_type = SQLSRV_FETCH_NUMERIC;

while ($row = sqlsrv_fetch_array($stmt, $fetch_type)) {
    var_dump($row);
    if ($fetch_type == SQLSRV_FETCH_NUMERIC) {
        $fetch_type = SQLSRV_FETCH_ASSOC;
    } elseif ($fetch_type == SQLSRV_FETCH_ASSOC) {
        $fetch_type = SQLSRV_FETCH_BOTH;
    } elseif ($fetch_type == SQLSRV_FETCH_BOTH) {
        $fetch_type = SQLSRV_FETCH_NUMERIC;
    }
}

// try some out of range values
$stmt = sqlsrv_query($conn, "SELECT * FROM [tracks];");
if ($stmt === false) {
    fatalError("sqlsrv_query failed");
}

$row = sqlsrv_fetch_array($stmt, 0);
if ($row !== false) {
    die("Invalid fetch type succeeded.");
}
print_r(sqlsrv_errors());
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_BOTH + 1);
if ($row !== false) {
    die("Invalid fetch type succeeded.");
}
print_r(sqlsrv_errors());

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

?>
--EXPECT--
array(2) {
  [0]=>
  string(13) "Casual Viewin"
  [1]=>
  string(10) "B00005N8UI"
}
array(2) {
  ["track"]=>
  string(10) "Since When"
  ["asin"]=>
  string(10) "B00005N8UI"
}
array(4) {
  [0]=>
  string(10) "I Go Blind"
  ["track"]=>
  string(10) "I Go Blind"
  [1]=>
  string(10) "B00005N8UI"
  ["asin"]=>
  string(10) "B00005N8UI"
}
array(2) {
  [0]=>
  string(8) "Blue Sky"
  [1]=>
  string(10) "B00005N8UI"
}
array(2) {
  ["track"]=>
  string(10) "Lies To Me"
  ["asin"]=>
  string(10) "B00005N8UI"
}
array(4) {
  [0]=>
  string(8) "Baby Ran"
  ["track"]=>
  string(8) "Baby Ran"
  [1]=>
  string(10) "B00005N8UI"
  ["asin"]=>
  string(10) "B00005N8UI"
}
array(2) {
  [0]=>
  string(7) "One Gun"
  [1]=>
  string(10) "B00005N8UI"
}
array(2) {
  ["track"]=>
  string(11) "Ocean Pearl"
  ["asin"]=>
  string(10) "B00005N8UI"
}
array(4) {
  [0]=>
  string(12) "Love You All"
  ["track"]=>
  string(12) "Love You All"
  [1]=>
  string(10) "B00005N8UI"
  ["asin"]=>
  string(10) "B00005N8UI"
}
array(2) {
  [0]=>
  string(16) "Nice To Love You"
  [1]=>
  string(10) "B00005N8UI"
}
array(2) {
  ["track"]=>
  string(12) "Shes A Jones"
  ["asin"]=>
  string(10) "B00005N8UI"
}
array(4) {
  [0]=>
  string(11) "Sunday Girl"
  ["track"]=>
  string(11) "Sunday Girl"
  [1]=>
  string(10) "B00005N8UI"
  ["asin"]=>
  string(10) "B00005N8UI"
}
array(2) {
  [0]=>
  string(11) "Lost & Lazy"
  [1]=>
  string(10) "B00005N8UI"
}
array(2) {
  ["track"]=>
  string(11) "Hells Bells"
  ["asin"]=>
  string(10) "B000002JS6"
}
array(4) {
  [0]=>
  string(15) "Shoot To Thrill"
  ["track"]=>
  string(15) "Shoot To Thrill"
  [1]=>
  string(10) "B000002JS6"
  ["asin"]=>
  string(10) "B000002JS6"
}
array(2) {
  [0]=>
  string(30) "What Do You Do For Money Honey"
  [1]=>
  string(10) "B000002JS6"
}
array(2) {
  ["track"]=>
  string(20) "Given The Dog A Bone"
  ["asin"]=>
  string(10) "B000002JS6"
}
array(4) {
  [0]=>
  string(27) "Let Me Put My Love Into You"
  ["track"]=>
  string(27) "Let Me Put My Love Into You"
  [1]=>
  string(10) "B000002JS6"
  ["asin"]=>
  string(10) "B000002JS6"
}
array(2) {
  [0]=>
  string(13) "Back In Black"
  [1]=>
  string(10) "B000002JS6"
}
array(2) {
  ["track"]=>
  string(27) "You Shook Me All Night Long"
  ["asin"]=>
  string(10) "B000002JS6"
}
array(4) {
  [0]=>
  string(18) "Have A Drink On Me"
  ["track"]=>
  string(18) "Have A Drink On Me"
  [1]=>
  string(10) "B000002JS6"
  ["asin"]=>
  string(10) "B000002JS6"
}
array(2) {
  [0]=>
  string(11) "Shake A Leg"
  [1]=>
  string(10) "B000002JS6"
}
array(2) {
  ["track"]=>
  string(34) "Rock And Roll Aint Noise Pollution"
  ["asin"]=>
  string(10) "B000002JS6"
}
array(4) {
  [0]=>
  string(15) "Highway To Hell"
  ["track"]=>
  string(15) "Highway To Hell"
  [1]=>
  string(10) "B00008BXJG"
  ["asin"]=>
  string(10) "B00008BXJG"
}
array(2) {
  [0]=>
  string(16) "Girls Got Rhythm"
  [1]=>
  string(10) "B00008BXJG"
}
array(2) {
  ["track"]=>
  string(17) "Walk All Over You"
  ["asin"]=>
  string(10) "B00008BXJG"
}
array(4) {
  [0]=>
  string(14) "Touch Too Much"
  ["track"]=>
  string(14) "Touch Too Much"
  [1]=>
  string(10) "B00008BXJG"
  ["asin"]=>
  string(10) "B00008BXJG"
}
array(2) {
  [0]=>
  string(23) "Beating Around The Bush"
  [1]=>
  string(10) "B00008BXJG"
}
array(2) {
  ["track"]=>
  string(19) "Shot Down In Flames"
  ["asin"]=>
  string(10) "B00008BXJG"
}
array(4) {
  [0]=>
  string(10) "Get It Hot"
  ["track"]=>
  string(10) "Get It Hot"
  [1]=>
  string(10) "B00008BXJG"
  ["asin"]=>
  string(10) "B00008BXJG"
}
array(2) {
  [0]=>
  string(32) "If You Want Blood (Youve Got It)"
  [1]=>
  string(10) "B00008BXJG"
}
array(2) {
  ["track"]=>
  string(15) "Love Hungry Man"
  ["asin"]=>
  string(10) "B00008BXJG"
}
array(4) {
  [0]=>
  string(13) "Night Prowler"
  ["track"]=>
  string(13) "Night Prowler"
  [1]=>
  string(10) "B00008BXJG"
  ["asin"]=>
  string(10) "B00008BXJG"
}
array(2) {
  [0]=>
  string(20) "Good Times Bad Times"
  [1]=>
  string(10) "B000002J01"
}
array(2) {
  ["track"]=>
  string(23) "Babe Im Gonna Leave You"
  ["asin"]=>
  string(10) "B000002J01"
}
array(4) {
  [0]=>
  string(12) "You Shook Me"
  ["track"]=>
  string(12) "You Shook Me"
  [1]=>
  string(10) "B000002J01"
  ["asin"]=>
  string(10) "B000002J01"
}
array(2) {
  [0]=>
  string(18) "Dazed And Confused"
  [1]=>
  string(10) "B000002J01"
}
array(2) {
  ["track"]=>
  string(23) "Your Time Is Gonna Come"
  ["asin"]=>
  string(10) "B000002J01"
}
array(4) {
  [0]=>
  string(19) "Black Mountain Side"
  ["track"]=>
  string(19) "Black Mountain Side"
  [1]=>
  string(10) "B000002J01"
  ["asin"]=>
  string(10) "B000002J01"
}
array(2) {
  [0]=>
  string(23) "Communication Breakdown"
  [1]=>
  string(10) "B000002J01"
}
array(2) {
  ["track"]=>
  string(20) "I Cant Quit You Baby"
  ["asin"]=>
  string(10) "B000002J01"
}
array(4) {
  [0]=>
  string(19) "How Many More Times"
  ["track"]=>
  string(19) "How Many More Times"
  [1]=>
  string(10) "B000002J01"
  ["asin"]=>
  string(10) "B000002J01"
}
array(2) {
  [0]=>
  string(14) "Good Time Boys"
  [1]=>
  string(10) "B000078DOI"
}
array(2) {
  ["track"]=>
  string(13) "Higher Ground"
  ["asin"]=>
  string(10) "B000078DOI"
}
array(4) {
  [0]=>
  string(15) "Subway To Venus"
  ["track"]=>
  string(15) "Subway To Venus"
  [1]=>
  string(10) "B000078DOI"
  ["asin"]=>
  string(10) "B000078DOI"
}
array(2) {
  [0]=>
  string(13) "Magic Johnson"
  [1]=>
  string(10) "B000078DOI"
}
array(2) {
  ["track"]=>
  string(20) "Nobody Weird Like Me"
  ["asin"]=>
  string(10) "B000078DOI"
}
array(4) {
  [0]=>
  string(13) "Knock Me Down"
  ["track"]=>
  string(13) "Knock Me Down"
  [1]=>
  string(10) "B000078DOI"
  ["asin"]=>
  string(10) "B000078DOI"
}
array(2) {
  [0]=>
  string(14) "Taste The Pain"
  [1]=>
  string(10) "B000078DOI"
}
array(2) {
  ["track"]=>
  string(15) "Stone Cold Bush"
  ["asin"]=>
  string(10) "B000078DOI"
}
array(4) {
  [0]=>
  string(4) "Fire"
  ["track"]=>
  string(4) "Fire"
  [1]=>
  string(10) "B000078DOI"
  ["asin"]=>
  string(10) "B000078DOI"
}
array(2) {
  [0]=>
  string(19) "Pretty Little Ditty"
  [1]=>
  string(10) "B000078DOI"
}
array(2) {
  ["track"]=>
  string(17) "Punk Rock Classic"
  ["asin"]=>
  string(10) "B000078DOI"
}
array(4) {
  [0]=>
  string(17) "Sexy Mexican Maid"
  ["track"]=>
  string(17) "Sexy Mexican Maid"
  [1]=>
  string(10) "B000078DOI"
  ["asin"]=>
  string(10) "B000078DOI"
}
array(2) {
  [0]=>
  string(30) "Johnny, Kick A Hole In The Sky"
  [1]=>
  string(10) "B000078DOI"
}
array(2) {
  ["track"]=>
  string(42) "Song That Made Us What We Are Today (Demo)"
  ["asin"]=>
  string(10) "B000078DOI"
}
array(4) {
  [0]=>
  string(37) "Knock Me Down (Original Long Version)"
  ["track"]=>
  string(37) "Knock Me Down (Original Long Version)"
  [1]=>
  string(10) "B000078DOI"
  ["asin"]=>
  string(10) "B000078DOI"
}
array(2) {
  [0]=>
  string(41) "Sexy Mexican Maid (Original Long Version)"
  [1]=>
  string(10) "B000078DOI"
}
array(2) {
  ["track"]=>
  string(23) "Salute To Kareem (Demo)"
  ["asin"]=>
  string(10) "B000078DOI"
}
array(4) {
  [0]=>
  string(27) "Castles Made Of Sand (Live)"
  ["track"]=>
  string(27) "Castles Made Of Sand (Live)"
  [1]=>
  string(10) "B000078DOI"
  ["asin"]=>
  string(10) "B000078DOI"
}
array(2) {
  [0]=>
  string(24) "Crosstown Traffic (Live)"
  [1]=>
  string(10) "B000078DOI"
}
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -10
            [code] => -10
            [2] => An invalid fetch type was specified. SQLSRV_FETCH_NUMERIC, SQLSRV_FETCH_ARRAY and SQLSRV_FETCH_BOTH are acceptable values.
            [message] => An invalid fetch type was specified. SQLSRV_FETCH_NUMERIC, SQLSRV_FETCH_ARRAY and SQLSRV_FETCH_BOTH are acceptable values.
        )

)
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -10
            [code] => -10
            [2] => An invalid fetch type was specified. SQLSRV_FETCH_NUMERIC, SQLSRV_FETCH_ARRAY and SQLSRV_FETCH_BOTH are acceptable values.
            [message] => An invalid fetch type was specified. SQLSRV_FETCH_NUMERIC, SQLSRV_FETCH_ARRAY and SQLSRV_FETCH_BOTH are acceptable values.
        )

)
