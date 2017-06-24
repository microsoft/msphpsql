USE $(dbname) 
GO

IF EXISTS (SELECT * FROM sys.objects 
WHERE object_id = OBJECT_ID(N'[dbo].[tracks]') AND type in (N'U'))

BEGIN
ALTER TABLE $(dbname)..[tracks] DROP CONSTRAINT [FK__tracks__asin__7F60ED59]
END

GO

IF EXISTS (SELECT * FROM sys.objects 
WHERE object_id = OBJECT_ID(N'[dbo].[cd_info]') AND type in (N'U'))

BEGIN
DROP TABLE [cd_info]
END

GO

/****** Object:  Table [dbo].[cd_info]    Script Date: 09/26/2007 11:26:52 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
SET ANSI_PADDING ON
GO
CREATE TABLE [dbo].[cd_info](
	[asin] [char](10) NOT NULL,
	[upc] [char](12) NULL,
	[title] [varchar](50) NULL,
	[artist] [varchar](50) NULL,
	[rel_date] [varchar](12) NOT NULL CONSTRAINT [DF_cd_info_rel_date]  DEFAULT ((0)),
	[label] [varchar](50) NULL,
	[image] [varchar](500) NULL,
	[med_image] [varchar](500) NULL,
	[review1] [varchar](max) NULL,
	[review2] [varchar](max) NULL,
 CONSTRAINT [PK_cd_info] PRIMARY KEY CLUSTERED 
(
	[asin] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) 
) 

GO
SET ANSI_PADDING OFF