--for this script to work in Azure, use sqlcmd to connect to master database 
IF NOT EXISTS (SELECT name FROM sys.sql_logins WHERE name = 'test_password')
BEGIN
    CREATE LOGIN test_password WITH PASSWORD='! ;4triou';
END
GO

IF NOT EXISTS (SELECT name FROM sys.sql_logins WHERE name = 'test_password2')
BEGIN
    CREATE LOGIN test_password2 WITH PASSWORD='!} ;4triou';
END
GO

IF NOT EXISTS (SELECT name FROM sys.sql_logins WHERE name = 'test_password3')
BEGIN
    CREATE LOGIN test_password3 WITH PASSWORD='! ;4triou}';
END
GO
