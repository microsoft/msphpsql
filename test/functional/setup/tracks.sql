USE $(dbname) 
GO

IF EXISTS (SELECT * FROM sys.objects 
WHERE object_id = OBJECT_ID(N'[dbo].[tracks]') AND type in (N'U'))

BEGIN
DROP TABLE [tracks]
END
GO

/****** Object:  Table [dbo].[tracks]    Script Date: 09/26/2007 11:33:41 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
SET ANSI_PADDING ON
GO
CREATE TABLE [dbo].[tracks](
	[track] [varchar](100) NULL,
	[asin] [char](10) NOT NULL
) ON [PRIMARY]
GO

SET ANSI_PADDING OFF
GO

ALTER TABLE [dbo].[tracks]  WITH NOCHECK ADD  CONSTRAINT [FK__tracks__asin__7F60ED59] FOREIGN KEY([asin])
REFERENCES [dbo].[cd_info] ([asin])
GO

ALTER TABLE [dbo].[tracks] CHECK CONSTRAINT [FK__tracks__asin__7F60ED59]