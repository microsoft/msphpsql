--for this script to work in Azure, create_logins_azure.sql must have been invoked beforehand
--assuming these logins exist, use sqlcmd to connect to a test database 
--these users will be granted access to that database
IF NOT EXISTS (SELECT name FROM sysusers WHERE name = 'test_password')
BEGIN
    CREATE USER test_password FROM LOGIN test_password;
END
GO

IF NOT EXISTS (SELECT name FROM sysusers WHERE name = 'test_password2')
BEGIN
    CREATE USER test_password2 FROM LOGIN test_password2;
END
GO

IF NOT EXISTS (SELECT name FROM sysusers WHERE name = 'test_password3')
BEGIN
    CREATE USER test_password3 FROM LOGIN test_password3;
END
GO

