<?php

// globals
$table1 =  "SS_AllTypes";

function create_table1($conn ) 
{
     global $table1;
     
     $create_query = 
        "IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'".$table1."') AND type in (N'U'))  DROP TABLE " . $table1 . 
        
        " CREATE TABLE " .$table1 . " (
	        [BigIntCol] [bigint] NULL,
	        [BinaryCol] [binary](5) NULL,
	        [BitCol] [bit] NULL,
	        [CharCol] [char](10) NULL,
	        [DateCol] [date] NULL,
	        [DateTimeCol] [datetime] NULL,
	        [DateTime2Col] [datetime2](7) NULL,
	        [DTOffsetCol] [datetimeoffset](7) NULL,
	        [DecimalCol] [decimal](18, 0) NULL,
	        [FloatCol] [float] NULL,
	        [ImageCol] [image] NULL,
	        [IntCol] [int] NULL,
	        [MoneyCol] [money] NULL,
	        [NCharCol] [nchar](10) NULL,
	        [NTextCol] [ntext] NULL,
	        [NumCol] [numeric](18, 0) NULL,
	        [NVarCharCol] [nvarchar](50) NULL,
	        [NVarCharMaxCol] [nvarchar](max) NULL,
	        [RealCol] [real] NULL,
	        [SmallDTCol] [smalldatetime] NULL,
	        [SmallIntCol] [smallint] NULL,
	        [SmallMoneyCol] [smallmoney] NULL,
	        [TextCol] [text] NULL,
	        [TimeCol] [time](7) NULL,
	        [TinyIntCol] [tinyint] NULL,
	        [Guidcol] [uniqueidentifier] NULL,
	        [VarbinaryCol] [varbinary](50) NULL,
	        [VarbinaryMaxCol] [varbinary](max) NULL,
	        [VarcharCol] [varchar](50) NULL,
	        [VarcharMaxCol] [varchar](max) NULL,
	        [XmlCol] [xml] NULL
        ) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
        
        ";   
       
    $stmt = sqlsrv_query($conn, $create_query );
    
    if( $stmt == false ) {
         print_r( sqlsrv_errors() );
         die("Statement creation failed");
    }   
}
?>
