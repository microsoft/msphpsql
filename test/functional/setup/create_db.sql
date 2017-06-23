USE [master]
GO

IF EXISTS (SELECT name FROM sys.databases WHERE name = '$(dbname)' )

BEGIN
DROP DATABASE $(dbname)
END

CREATE DATABASE $(dbname)

GO

