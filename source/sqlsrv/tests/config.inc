<?php
$serverName = getenv('MSSQL_SERVERNAME') ?: 'localhost';
$database   = getenv('MSSQL_DATABASE') ?: 'test_db';
$username   = getenv('MSSQL_USERNAME') ?: 'test_user';
$password   = getenv('MSSQL_PASSWORD') ?: 'test_pass';

