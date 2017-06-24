USE $(dbname) 
GO

CREATE TABLE [test_types] ([bigint_type] BIGINT null,
                                      [int_type] INT null,
                                      [smallint_type] SMALLINT null,
                                      [tinyint_type] TINYINT null,
                                      [bit_type] BIT null,
                                      [decimal_type] DECIMAL(38,0) null,
                                      [money_type] MONEY null,
                                      [smallmoney_type] SMALLMONEY null,
                                      [float_type] FLOAT(53) null,
                                      [real_type] REAL null,
                                      [datetime_type] DATETIME null,
                                      [smalldatetime_type] SMALLDATETIME null );
GO

-- maximum test
INSERT INTO $(dbname)..[test_types] (bigint_type, int_type, smallint_type, tinyint_type, bit_type, decimal_type, datetime_type, money_type, smallmoney_type, float_type, real_type) 
			VALUES (9223372036854775807, 2147483647, 32767, 255, 1, 9999999999999999999999999999999999999, '12/12/1968 16:20', 922337203685477.5807, 214748.3647, 1.79E+308, 1.18E-38 )
-- minimum test
INSERT INTO $(dbname)..[test_types] (bigint_type, int_type, smallint_type, tinyint_type, bit_type, decimal_type, datetime_type, money_type, smallmoney_type, float_type, real_type)
			VALUES (-9223372036854775808, -2147483648, -32768, 0, 0, -10000000000000000000000000000000000001,'12/12/1968 16:20', -922337203685477.5808, -214748.3648, -1.79E+308, -1.18E-38 )
-- zero test
INSERT INTO $(dbname)..[test_types] (bigint_type, int_type, smallint_type, tinyint_type, bit_type, decimal_type, datetime_type, money_type, smallmoney_type, float_type, real_type) 
			VALUES (0, 0, 0, 0, 0, 0, '12/12/1968 16:20', 0, 0, 0, 0)

GO

CREATE TABLE [test_streamable_types] ( 
    [varchar_type] VARCHAR(MAX) null,
    [nvarchar_type] NVARCHAR(MAX) null,
    [varbinary_type] VARBINARY(MAX) null,
    [text_type] TEXT null,
    [ntext_type] NTEXT null,
    [image_type] IMAGE null,
    [xml_type] XML null,
    [char_short_type] CHAR(256) null,
    [varchar_short_type] VARCHAR(256) null,
    [nchar_short_type] NCHAR(256) null,
    [nvarchar_short_type] NVARCHAR(256) null,
    [binary_short_type] BINARY(256) null,
    [varbinary_short_type] VARBINARY(256) null );
GO

CREATE TABLE [155671] ([cat_id] [int] IDENTITY (1,1) NOT NULL, [cat_title] [varchar](50) NOT NULL, [cat_order][int] NOT NULL) ON [PRIMARY];
GO

CREATE TABLE [159137] ([xml_type][xml] null) ON [PRIMARY];
GO

IF EXISTS ( SELECT  *
            FROM    sys.objects
            WHERE   object_id = OBJECT_ID(N'test_out')
                    AND type IN ( N'P', N'PC' ) ) 
BEGIN
DROP proc test_out
END
GO

create proc test_out @p1 integer, @p2 integer, @p3 integer output
as
begin
	select @p3 = @p1 + @p2
	print @p3
end
go
