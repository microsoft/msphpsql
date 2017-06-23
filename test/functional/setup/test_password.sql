--first, create new logins (user id / password pair) if not yet created
USE master;
GO

IF NOT EXISTS (SELECT name FROM master..syslogins WHERE name = 'test_password')
BEGIN
    CREATE LOGIN test_password WITH PASSWORD='! ;4triou';
END
GO

IF NOT EXISTS (SELECT name FROM master..syslogins WHERE name = 'test_password2')
BEGIN
    CREATE LOGIN test_password2 WITH PASSWORD='!} ;4triou';
END
GO

IF NOT EXISTS (SELECT name FROM master..syslogins WHERE name = 'test_password3')
BEGIN
    CREATE LOGIN test_password3 WITH PASSWORD='! ;4triou}';
END
GO

--the following users will be granted access to the test database 
USE $(dbname);
GO

CREATE USER test_password FROM LOGIN test_password;
CREATE USER test_password2 FROM LOGIN test_password2;
CREATE USER test_password3 FROM LOGIN test_password3;
GO

