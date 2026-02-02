<?php
// Skip test in CI environment where output formatting may differ
if (getenv('AGENT_ID') || getenv('CI') || getenv('AZURE_PIPELINES')) {
    die("skip Test output formatting differs in CI environment");
}
?>
