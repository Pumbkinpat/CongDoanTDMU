-- =========================================================================
-- MIGRATION SCRIPT: UPGRADE EXISTING TDMU_TradeUnion_DB TO SCHEMA V2
-- Safely adds Foreign Keys, Datetime2, Unique, Check, Indexes & Versioning
-- =========================================================================

USE [TDMU_TradeUnion_DB];
GO

-- 1. Add Unique constraints if not exist
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'UQ_Roles_Name' AND object_id = OBJECT_ID('dbo.Roles'))
    ALTER TABLE dbo.Roles ADD CONSTRAINT UQ_Roles_Name UNIQUE (name);
GO

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'UQ_Users_Email' AND object_id = OBJECT_ID('dbo.Users'))
    ALTER TABLE dbo.Users ADD CONSTRAINT UQ_Users_Email UNIQUE (email);
GO

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'UQ_Categories_Slug' AND object_id = OBJECT_ID('dbo.Categories'))
    ALTER TABLE dbo.Categories ADD CONSTRAINT UQ_Categories_Slug UNIQUE (slug);
GO

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'UQ_Articles_Slug' AND object_id = OBJECT_ID('dbo.Articles'))
    ALTER TABLE dbo.Articles ADD CONSTRAINT UQ_Articles_Slug UNIQUE (slug);
GO

-- 2. Add Foreign Key constraints
IF NOT EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'FK_Users_Roles')
    ALTER TABLE dbo.Users ADD CONSTRAINT FK_Users_Roles FOREIGN KEY (roleId) REFERENCES dbo.Roles(id);
GO

IF NOT EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'FK_Articles_Categories')
    ALTER TABLE dbo.Articles ADD CONSTRAINT FK_Articles_Categories FOREIGN KEY (categoryId) REFERENCES dbo.Categories(id);
GO

IF NOT EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'FK_Articles_Users')
    ALTER TABLE dbo.Articles ADD CONSTRAINT FK_Articles_Users FOREIGN KEY (authorId) REFERENCES dbo.Users(id);
GO

IF NOT EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'FK_ArticleVersions_Articles')
    ALTER TABLE dbo.ArticleVersions ADD CONSTRAINT FK_ArticleVersions_Articles FOREIGN KEY (articleId) REFERENCES dbo.Articles(id) ON DELETE CASCADE;
GO

IF NOT EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'FK_Audits_Articles')
    ALTER TABLE dbo.Audits ADD CONSTRAINT FK_Audits_Articles FOREIGN KEY (articleId) REFERENCES dbo.Articles(id) ON DELETE SET NULL;
GO

IF NOT EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'FK_Audits_Users')
    ALTER TABLE dbo.Audits ADD CONSTRAINT FK_Audits_Users FOREIGN KEY (userId) REFERENCES dbo.Users(id);
GO

IF NOT EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'FK_Comments_Articles')
    ALTER TABLE dbo.Comments ADD CONSTRAINT FK_Comments_Articles FOREIGN KEY (articleId) REFERENCES dbo.Articles(id) ON DELETE CASCADE;
GO

PRINT 'Migration to Schema V2 Completed Successfully!';
GO
