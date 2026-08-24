-- =========================================================================
-- MICROSOFT SQL SERVER ENTERPRISE SCHEMA (RICH DATA MODEL FOR TDMU TRADE UNION)
-- Database Name: TDMU_TradeUnion_DB
-- =========================================================================

IF NOT EXISTS (SELECT * FROM sys.databases WHERE name = N'TDMU_TradeUnion_DB')
BEGIN
    CREATE DATABASE [TDMU_TradeUnion_DB];
END
GO

USE [TDMU_TradeUnion_DB];
GO

-- 1. Table Roles (Phân quyền bảo mật hệ thống)
IF OBJECT_ID(N'dbo.Roles', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.Roles (
        id INT PRIMARY KEY IDENTITY(1,1),
        name NVARCHAR(50) NOT NULL CONSTRAINT UQ_Roles_Name UNIQUE,
        displayName NVARCHAR(100) NOT NULL,
        description NVARCHAR(255) NULL,
        createdAt DATETIME2 NOT NULL CONSTRAINT DF_Roles_CreatedAt DEFAULT SYSDATETIME()
    );
END;
GO

-- 2. Table UnionDepartments (13 Tổ Công Đoàn Bộ Phận TDMU)
IF OBJECT_ID(N'dbo.UnionDepartments', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.UnionDepartments (
        id INT PRIMARY KEY IDENTITY(1,1),
        code NVARCHAR(50) NOT NULL CONSTRAINT UQ_UnionDepartments_Code UNIQUE,
        name NVARCHAR(150) NOT NULL,
        shortName NVARCHAR(50) NULL,
        leaderName NVARCHAR(100) NULL,
        leaderTitle NVARCHAR(50) NULL,
        memberCount INT NOT NULL CONSTRAINT DF_UnionDepartments_MemberCount DEFAULT 0,
        contactEmail NVARCHAR(100) NULL,
        contactPhone NVARCHAR(20) NULL,
        officeLocation NVARCHAR(150) NULL,
        createdAt DATETIME2 NOT NULL CONSTRAINT DF_UnionDepartments_CreatedAt DEFAULT SYSDATETIME()
    );
END;
GO

-- 3. Table UnionMembers (Hồ sơ cán bộ & đoàn viên chi tiết)
IF OBJECT_ID(N'dbo.UnionMembers', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.UnionMembers (
        id INT PRIMARY KEY IDENTITY(1,1),
        memberCode NVARCHAR(50) NOT NULL CONSTRAINT UQ_UnionMembers_MemberCode UNIQUE,
        staffCode NVARCHAR(50) NULL,
        fullName NVARCHAR(100) NOT NULL,
        gender NVARCHAR(10) NULL,
        birthDate DATE NULL,
        email NVARCHAR(100) NOT NULL CONSTRAINT UQ_UnionMembers_Email UNIQUE,
        phone NVARCHAR(20) NULL,
        academicRank NVARCHAR(50) NULL, -- Giáo sư, Phó Giáo sư
        academicDegree NVARCHAR(50) NULL, -- Tiến sĩ, Thạc sĩ, Cử nhân, Kỹ sư
        politicalLevel NVARCHAR(50) NULL, -- Cao cấp, Trung cấp, Sơ cấp LLCT
        unionPosition NVARCHAR(100) NOT NULL CONSTRAINT DF_UnionMembers_Position DEFAULT N'Đoàn viên', -- Chủ tịch, Phó Chủ tịch, Ủy viên BTV, Tổ trưởng CĐ
        facultyPosition NVARCHAR(100) NULL, -- Trưởng khoa, Giảng viên chính, Chuyên viên
        departmentId INT NOT NULL,
        joinedUnionDate DATE NULL,
        joinedPartyDate DATE NULL,
        isActive BIT NOT NULL CONSTRAINT DF_UnionMembers_IsActive DEFAULT 1,
        createdAt DATETIME2 NOT NULL CONSTRAINT DF_UnionMembers_CreatedAt DEFAULT SYSDATETIME(),
        CONSTRAINT FK_UnionMembers_Departments FOREIGN KEY (departmentId) REFERENCES dbo.UnionDepartments(id)
    );
END;
GO

-- 4. Table Users (Tài khoản cán bộ quản trị & tác giả hệ thống)
IF OBJECT_ID(N'dbo.Users', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.Users (
        id INT PRIMARY KEY IDENTITY(1,1),
        memberId INT NULL,
        name NVARCHAR(100) NOT NULL,
        email NVARCHAR(100) NOT NULL CONSTRAINT UQ_Users_Email UNIQUE,
        passwordHash NVARCHAR(255) NULL,
        roleId INT NOT NULL,
        department NVARCHAR(100) NULL,
        createdAt DATETIME2 NOT NULL CONSTRAINT DF_Users_CreatedAt DEFAULT SYSDATETIME(),
        CONSTRAINT FK_Users_Roles FOREIGN KEY (roleId) REFERENCES dbo.Roles(id),
        CONSTRAINT FK_Users_Members FOREIGN KEY (memberId) REFERENCES dbo.UnionMembers(id) ON DELETE SET NULL
    );
END;
GO

-- 5. Table Categories (Danh mục bài viết truyền thông)
IF OBJECT_ID(N'dbo.Categories', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.Categories (
        id INT PRIMARY KEY IDENTITY(1,1),
        name NVARCHAR(100) NOT NULL,
        slug NVARCHAR(100) NOT NULL CONSTRAINT UQ_Categories_Slug UNIQUE,
        description NVARCHAR(255) NULL,
        orderIndex INT NOT NULL CONSTRAINT DF_Categories_Order DEFAULT 1,
        createdAt DATETIME2 NOT NULL CONSTRAINT DF_Categories_CreatedAt DEFAULT SYSDATETIME()
    );
END;
GO

-- 6. Table Events (Sự kiện phong trào, hội thao, tọa đàm)
IF OBJECT_ID(N'dbo.Events', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.Events (
        id INT PRIMARY KEY IDENTITY(1,1),
        eventCode NVARCHAR(50) NOT NULL CONSTRAINT UQ_Events_Code UNIQUE,
        title NVARCHAR(255) NOT NULL,
        eventType NVARCHAR(100) NOT NULL, -- Hội thao, Tọa đàm, Đại hội, Tập huấn, Thi đua
        organizerUnitId INT NULL,
        location NVARCHAR(255) NULL,
        roomNumber NVARCHAR(50) NULL,
        startTime DATETIME2 NOT NULL,
        endTime DATETIME2 NULL,
        registrationDeadline DATETIME2 NULL,
        budgetEstimated DECIMAL(18,2) NOT NULL CONSTRAINT DF_Events_Budget DEFAULT 0,
        budgetActual DECIMAL(18,2) NULL,
        description NVARCHAR(MAX) NULL,
        attendeesCount INT NOT NULL CONSTRAINT DF_Events_AttendeesCount DEFAULT 0 CONSTRAINT CK_Events_AttendeesCount CHECK (attendeesCount >= 0),
        status NVARCHAR(50) NOT NULL CONSTRAINT DF_Events_Status DEFAULT 'upcoming' CONSTRAINT CK_Events_Status CHECK (status IN ('draft', 'upcoming', 'ongoing', 'completed', 'cancelled')),
        createdAt DATETIME2 NOT NULL CONSTRAINT DF_Events_CreatedAt DEFAULT SYSDATETIME(),
        CONSTRAINT FK_Events_Departments FOREIGN KEY (organizerUnitId) REFERENCES dbo.UnionDepartments(id) ON DELETE SET NULL
    );
END;
GO

-- 7. Table Articles (Thực thể bài viết truyền thông toàn diện)
IF OBJECT_ID(N'dbo.Articles', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.Articles (
        id INT PRIMARY KEY IDENTITY(101,1),
        title NVARCHAR(255) NOT NULL,
        subTitle NVARCHAR(255) NULL,
        slug NVARCHAR(255) NOT NULL CONSTRAINT UQ_Articles_Slug UNIQUE,
        categoryId INT NOT NULL,
        authorId INT NOT NULL, -- Cán bộ / Tác giả trực tiếp soạn thảo
        publisherId INT NULL,  -- Cán bộ phê duyệt xuất bản
        issuingUnit NVARCHAR(150) NOT NULL CONSTRAINT DF_Articles_IssuingUnit DEFAULT N'Ban Thường Vụ Công Đoàn TDMU', -- Đơn vị ban hành chính thức
        eventId INT NULL,      -- Liên kết sự kiện nguồn
        summary NVARCHAR(MAX) NULL,
        content NVARCHAR(MAX) NULL,
        image NVARCHAR(MAX) NULL,
        imageCaption NVARCHAR(255) NULL,
        readingTimeMinutes INT NOT NULL CONSTRAINT DF_Articles_ReadingTime DEFAULT 3,
        tags NVARCHAR(255) NULL, -- JSON array hoặc chuỗi tag
        status NVARCHAR(50) NOT NULL CONSTRAINT DF_Articles_Status DEFAULT 'published' CONSTRAINT CK_Articles_Status CHECK (status IN ('draft', 'pending_review', 'approved', 'scheduled', 'published', 'rejected', 'archived')),
        isAiGenerated BIT NOT NULL CONSTRAINT DF_Articles_IsAiGenerated DEFAULT 0,
        viewsCount INT NOT NULL CONSTRAINT DF_Articles_ViewsCount DEFAULT 0 CONSTRAINT CK_Articles_ViewsCount CHECK (viewsCount >= 0),
        likesCount INT NOT NULL CONSTRAINT DF_Articles_LikesCount DEFAULT 0 CONSTRAINT CK_Articles_LikesCount CHECK (likesCount >= 0),
        sharesCount INT NOT NULL CONSTRAINT DF_Articles_SharesCount DEFAULT 0 CONSTRAINT CK_Articles_SharesCount CHECK (sharesCount >= 0),
        scheduledAt DATETIME2 NULL,
        publishedAt DATETIME2 NULL,
        createdAt DATETIME2 NOT NULL CONSTRAINT DF_Articles_CreatedAt DEFAULT SYSDATETIME(),
        updatedAt DATETIME2 NOT NULL CONSTRAINT DF_Articles_UpdatedAt DEFAULT SYSDATETIME(),
        CONSTRAINT FK_Articles_Categories FOREIGN KEY (categoryId) REFERENCES dbo.Categories(id),
        CONSTRAINT FK_Articles_Authors FOREIGN KEY (authorId) REFERENCES dbo.Users(id),
        CONSTRAINT FK_Articles_Publishers FOREIGN KEY (publisherId) REFERENCES dbo.Users(id),
        CONSTRAINT FK_Articles_Events FOREIGN KEY (eventId) REFERENCES dbo.Events(id) ON DELETE SET NULL
    );
END;
GO

-- 8. Table ArticleVersions (Lưu dòng dõi phiên bản AI & Cán bộ sửa bài)
IF OBJECT_ID(N'dbo.ArticleVersions', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.ArticleVersions (
        id INT PRIMARY KEY IDENTITY(1,1),
        articleId INT NOT NULL,
        versionNumber INT NOT NULL,
        title NVARCHAR(255) NOT NULL,
        content NVARCHAR(MAX) NULL,
        createdBy INT NULL,
        changeType NVARCHAR(50) NOT NULL CONSTRAINT DF_ArticleVersions_ChangeType DEFAULT 'EDITOR_EDIT' 
            CONSTRAINT CK_ArticleVersions_ChangeType CHECK (changeType IN ('AI_GENERATED', 'AI_REWRITE', 'AI_SHORTEN', 'AI_EXPAND', 'AI_SPELLING_FIX', 'EDITOR_EDIT', 'ADMIN_EDIT', 'RESTORE_VERSION')),
        isAiGenerated BIT NOT NULL CONSTRAINT DF_ArticleVersions_IsAiGenerated DEFAULT 0,
        aiProvider NVARCHAR(50) NULL,
        aiModel NVARCHAR(50) NULL,
        aiPrompt NVARCHAR(MAX) NULL,
        createdAt DATETIME2 NOT NULL CONSTRAINT DF_ArticleVersions_CreatedAt DEFAULT SYSDATETIME(),
        CONSTRAINT UQ_ArticleVersions_Article_Version UNIQUE (articleId, versionNumber),
        CONSTRAINT FK_ArticleVersions_Articles FOREIGN KEY (articleId) REFERENCES dbo.Articles(id) ON DELETE CASCADE,
        CONSTRAINT FK_ArticleVersions_Users FOREIGN KEY (createdBy) REFERENCES dbo.Users(id) ON DELETE SET NULL
    );
END;
GO

-- 9. Table Audits (Nhật ký kiểm toán bảo mật bất biến)
IF OBJECT_ID(N'dbo.Audits', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.Audits (
        id INT PRIMARY KEY IDENTITY(1,1),
        articleId INT NULL,
        userId INT NOT NULL,
        action NVARCHAR(100) NOT NULL,
        details NVARCHAR(MAX) NULL,
        ipAddress NVARCHAR(50) NULL,
        createdAt DATETIME2 NOT NULL CONSTRAINT DF_Audits_CreatedAt DEFAULT SYSDATETIME(),
        CONSTRAINT FK_Audits_Articles FOREIGN KEY (articleId) REFERENCES dbo.Articles(id) ON DELETE SET NULL,
        CONSTRAINT FK_Audits_Users FOREIGN KEY (userId) REFERENCES dbo.Users(id)
    );
END;
GO

-- 10. Table Comments (Bình luận & phản hồi của đoàn viên)
IF OBJECT_ID(N'dbo.Comments', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.Comments (
        id INT PRIMARY KEY IDENTITY(1,1),
        articleId INT NOT NULL,
        userId INT NULL,
        authorName NVARCHAR(100) NOT NULL,
        commentText NVARCHAR(MAX) NOT NULL,
        platform NVARCHAR(50) NOT NULL CONSTRAINT DF_Comments_Platform DEFAULT 'Website' CONSTRAINT CK_Comments_Platform CHECK (platform IN ('Website', 'Facebook', 'Zalo')),
        createdAt DATETIME2 NOT NULL CONSTRAINT DF_Comments_CreatedAt DEFAULT SYSDATETIME(),
        CONSTRAINT FK_Comments_Articles FOREIGN KEY (articleId) REFERENCES dbo.Articles(id) ON DELETE CASCADE,
        CONSTRAINT FK_Comments_Users FOREIGN KEY (userId) REFERENCES dbo.Users(id) ON DELETE SET NULL
    );
END;
GO
