<?php
require('skipif.inc');
require_once('MsCommon.inc');

// AE fixture inserts bind values instead of embedding ANSI bytes in command text.
if (!AE\isColEncrypted()) {
    require('skipif_ansi_fixture.inc');
}
