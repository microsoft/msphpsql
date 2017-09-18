certificate.ps1: For setting up certificate store and create master key and encryption key

AEData.inc: contains data for insertion

databasesetup.php: create tables in a database that contain most SQL data types and insert data (from AEData.inc) into the created tabled

encrypttable.ps1: encrypt existing tables (need to be add to to encrypt columns in tables created from databasesetup.php) using encryption key created in certificate.ps1


AESetup.inc: contains helper functions for testing AEData

ae_int.php: php script for testing insertion and fetching of encrypted columns (more will be added to test all data types)