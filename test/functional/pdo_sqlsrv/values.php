<?php

// This file holds different data of many different types for testing
// Always Encrypted. Currently, the tests that use this data are:

// pdo_ae_azure_key_vault_keywords.phpt ($small_values)
// pdo_ae_azure_key_vault_username_password.phpt ($small_values)
// pdo_ae_azure_key_vault_client_secret.phpt ($small_values)

// The orders of the array elements below correspond to the column
// data types defined in the tests above.

// For the $small_values array, the string size of 64 is large enough
// to hold every string value.
const SHORT_STRSIZE = 64;

// The bigint field must be inserted as a string to maintain accuracy
$small_values = array("qwerty",
                      "wertyu",
                      "ϕƆǀđIΩͰǱζ±Áɔd͋ǻĆÅũμ",
                      52.7878,
                      -1.79E+308,
                      -3.4E+38,
                      "-9223372036854775807",
                      987654321,
                      1,
                      );

// For the $values array, define two string sizes because there are
// two strings for each (non-max) type so that we can test
// conversions from a shorter type to a longer type.
const STRSIZE = 256;
const LONG_STRSIZE = 384;

?>