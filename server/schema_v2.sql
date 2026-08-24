-- =========================================================================
-- MICROSOFT SQL SERVER PRODUCTION-GRADE ENTERPRISE DATABASE SCHEMA (V2)
-- Database Name: TDMU_TradeUnion_DB
-- Architecture: Fully Normalized (3NF), Referential Integrity (FKs),
-- Check Constraints, Unique Keys, Datetime2, AI Versioning & Performance Indexes.
-- =========================================================================

IF NOT EXISTS (SELECT * FROM sys.databases WHERE name = N'TDMU_TradeUnion_DB')
BEGIN
    CREATE DATABASE [TDMU_TradeUnion_DB];
END
GO

USE [TDMU_TradeUnion_DB];
GO

-- -------------------------------------------------------------------------
-- 1. Table Roles (Phân quyền hệ thống)
-- -------------------------------------------------------------------------
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

-- -------------------------------------------------------------------------
-- 2. Table Users (Tài khoản cán bộ công đoàn)
-- -------------------------------------------------------------------------
IF OBJECT_ID(N'dbo.Users', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.Users (
        id INT PRIMARY KEY IDENTITY(1,1),
        name NVARCHAR(100) NOT NULL,
        email NVARCHAR(100) NOT NULL CONSTRAINT UQ_Users_Email UNIQUE,
        passwordHash NVARCHAR(255) NULL,
        roleId INT NOT NULL,
        department NVARCHAR(100) NULL,
        createdAt DATETIME2 NOT NULL CONSTRAINT DF_Users_CreatedAt DEFAULT SYSDATETIME(),
        CONSTRAINT FK_Users_Roles FOREIGN KEY (roleId) REFERENCES dbo.Roles(id)
    );
END;
GO

-- -------------------------------------------------------------------------
-- 3. Table Categories (Chuyên mục bài viết)
-- -------------------------------------------------------------------------
IF OBJECT_ID(N'dbo.Categories', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.Categories (
        id INT PRIMARY KEY IDENTITY(1,1),
        name NVARCHAR(100) NOT NULL,
        slug NVARCHAR(100) NOT NULL CONSTRAINT UQ_Categories_Slug UNIQUE,
        description NVARCHAR(255) NULL,
        createdAt DATETIME2 NOT NULL CONSTRAINT DF_Categories_CreatedAt DEFAULT SYSDATETIME()
    );
END;
GO

-- -------------------------------------------------------------------------
-- 4. Table Events (Sự kiện hội thao, tọa đàm - Nguồn dữ liệu AI sinh bài)
-- -------------------------------------------------------------------------
IF OBJECT_ID(N'dbo.Events', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.Events (
        id INT PRIMARY KEY IDENTITY(1,1),
        title NVARCHAR(255) NOT NULL,
        location NVARCHAR(255) NULL,
        startTime DATETIME2 NOT NULL,
        endTime DATETIME2 NULL,
        description NVARCHAR(MAX) NULL,
        attendeesCount INT NOT NULL CONSTRAINT DF_Events_AttendeesCount DEFAULT 0 CONSTRAINT CK_Events_AttendeesCount CHECK (attendeesCount >= 0),
        createdAt DATETIME2 NOT NULL CONSTRAINT DF_Events_CreatedAt DEFAULT SYSDATETIME()
    );
END;
GO

-- -------------------------------------------------------------------------
-- 5. Table Media (Kho lưu trữ tệp & ảnh truyền thông)
-- -------------------------------------------------------------------------
IF OBJECT_ID(N'dbo.Media', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.Media (
        id INT PRIMARY KEY IDENTITY(1,1),
        fileName NVARCHAR(255) NOT NULL,
        filePath NVARCHAR(MAX) NOT NULL,
        mimeType NVARCHAR(50) NULL,
        fileSizeBytes BIGINT NOT NULL CONSTRAINT DF_Media_FileSizeBytes DEFAULT 0,
        category NVARCHAR(100) NULL,
        uploadedBy INT NULL,
        uploadedAt DATETIME2 NOT NULL CONSTRAINT DF_Media_UploadedAt DEFAULT SYSDATETIME(),
        CONSTRAINT FK_Media_Users FOREIGN KEY (uploadedBy) REFERENCES dbo.Users(id) ON DELETE SET NULL
    );
END;
GO

-- -------------------------------------------------------------------------
-- 6. Table Articles (Thực thể trung tâm: Quản lý bài viết)
-- Chuẩn hóa: Đã loại bỏ categoryName, author, statusName lặp thừa.
-- -------------------------------------------------------------------------
IF OBJECT_ID(N'dbo.Articles', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.Articles (
        id INT PRIMARY KEY IDENTITY(101,1),
        title NVARCHAR(255) NOT NULL,
        slug NVARCHAR(255) NOT NULL CONSTRAINT UQ_Articles_Slug UNIQUE,
        categoryId INT NOT NULL,
        authorId INT NOT NULL,
        eventId INT NULL,
        summary NVARCHAR(MAX) NULL,
        content NVARCHAR(MAX) NULL,
        image NVARCHAR(MAX) NULL,
        status NVARCHAR(50) NOT NULL CONSTRAINT DF_Articles_Status DEFAULT 'published' CONSTRAINT CK_Articles_Status CHECK (status IN ('draft', 'pending_review', 'approved', 'scheduled', 'published', 'rejected', 'archived')),
        isAiGenerated BIT NOT NULL CONSTRAINT DF_Articles_IsAiGenerated DEFAULT 0,
        viewsCount INT NOT NULL CONSTRAINT DF_Articles_ViewsCount DEFAULT 0 CONSTRAINT CK_Articles_ViewsCount CHECK (viewsCount >= 0),
        likesCount INT NOT NULL CONSTRAINT DF_Articles_LikesCount DEFAULT 0 CONSTRAINT CK_Articles_LikesCount CHECK (likesCount >= 0),
        sharesCount INT NOT NULL CONSTRAINT DF_Articles_SharesCount DEFAULT 0 CONSTRAINT CK_Articles_SharesCount CHECK (sharesCount >= 0),
        scheduledAt DATETIME2 NULL,
        createdAt DATETIME2 NOT NULL CONSTRAINT DF_Articles_CreatedAt DEFAULT SYSDATETIME(),
        updatedAt DATETIME2 NOT NULL CONSTRAINT DF_Articles_UpdatedAt DEFAULT SYSDATETIME(),
        CONSTRAINT FK_Articles_Categories FOREIGN KEY (categoryId) REFERENCES dbo.Categories(id),
        CONSTRAINT FK_Articles_Users FOREIGN KEY (authorId) REFERENCES dbo.Users(id),
        CONSTRAINT FK_Articles_Events FOREIGN KEY (eventId) REFERENCES dbo.Events(id) ON DELETE SET NULL
    );
END;
GO

-- -------------------------------------------------------------------------
-- 7. Table ArticleVersions (Lưu vết lịch sử AI & Cán bộ sửa bài - AI Audit Trail)
-- -------------------------------------------------------------------------
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

-- -------------------------------------------------------------------------
-- 8. Table Audits (Nhật ký kiểm toán lịch sử thao tác hệ thống)
-- -------------------------------------------------------------------------
IF OBJECT_ID(N'dbo.Audits', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.Audits (
        id INT PRIMARY KEY IDENTITY(1,1),
        articleId INT NULL,
        userId INT NOT NULL,
        action NVARCHAR(100) NOT NULL,
        details NVARCHAR(MAX) NULL,
        createdAt DATETIME2 NOT NULL CONSTRAINT DF_Audits_CreatedAt DEFAULT SYSDATETIME(),
        CONSTRAINT FK_Audits_Articles FOREIGN KEY (articleId) REFERENCES dbo.Articles(id) ON DELETE SET NULL,
        CONSTRAINT FK_Audits_Users FOREIGN KEY (userId) REFERENCES dbo.Users(id)
    );
END;
GO

-- -------------------------------------------------------------------------
-- 9. Table Comments (Bình luận phản hồi của đoàn viên)
-- -------------------------------------------------------------------------
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

-- =========================================================================
-- PERFORMANCE INDEXES (Tối ưu hóa tốc độ truy vấn CMS & AI)
-- =========================================================================
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_Articles_Status_CreatedAt' AND object_id = OBJECT_ID('dbo.Articles'))
BEGIN
    CREATE NONCLUSTERED INDEX IX_Articles_Status_CreatedAt ON dbo.Articles(status, createdAt DESC) INCLUDE (title, slug, categoryId, authorId, image, viewsCount);
END;
GO

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_Articles_CategoryId' AND object_id = OBJECT_ID('dbo.Articles'))
BEGIN
    CREATE NONCLUSTERED INDEX IX_Articles_CategoryId ON dbo.Articles(categoryId);
END;
GO

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_Articles_AuthorId' AND object_id = OBJECT_ID('dbo.Articles'))
BEGIN
    CREATE NONCLUSTERED INDEX IX_Articles_AuthorId ON dbo.Articles(authorId);
END;
GO

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_ArticleVersions_ArticleId' AND object_id = OBJECT_ID('dbo.ArticleVersions'))
BEGIN
    CREATE NONCLUSTERED INDEX IX_ArticleVersions_ArticleId ON dbo.ArticleVersions(articleId, versionNumber DESC);
END;
GO

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_Audits_ArticleId' AND object_id = OBJECT_ID('dbo.Audits'))
BEGIN
    CREATE NONCLUSTERED INDEX IX_Audits_ArticleId ON dbo.Audits(articleId);
END;
GO

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_Comments_ArticleId' AND object_id = OBJECT_ID('dbo.Comments'))
BEGIN
    CREATE NONCLUSTERED INDEX IX_Comments_ArticleId ON dbo.Comments(articleId);
END;
GO

-- =========================================================================
-- SEED INITIAL NORMALIZED DATA (Khởi tạo dữ liệu chuẩn quan hệ)
-- =========================================================================

-- Seed Roles
IF NOT EXISTS (SELECT * FROM dbo.Roles WHERE name = 'admin')
BEGIN
    INSERT INTO dbo.Roles (name, displayName, description) VALUES
    ('admin', N'Quản Trị Viên (Admin)', N'Toàn quyền kiểm soát và quản lý hệ thống'),
    ('editor', N'Biên Tập Viên (Editor)', N'Biên tập, phê duyệt bài viết và đặt lịch xuất bản'),
    ('contributor', N'Cộng Tác Viên (Contributor)', N'Soạn thảo bài viết, sử dụng AI sáng tạo nội dung');
END;
GO

-- Seed Users
IF NOT EXISTS (SELECT * FROM dbo.Users WHERE email = 'admin@tdmu.edu.vn')
BEGIN
    INSERT INTO dbo.Users (name, email, roleId, department) VALUES
    (N'Thầy Nguyễn Văn A', 'admin@tdmu.edu.vn', (SELECT id FROM dbo.Roles WHERE name='admin'), N'Ban Chấp Hành Công Đoàn'),
    (N'Cô Trần Thị B', 'editor@tdmu.edu.vn', (SELECT id FROM dbo.Roles WHERE name='editor'), N'Ban Truyền Thông'),
    (N'Thầy Lê Văn C', 'contributor@tdmu.edu.vn', (SELECT id FROM dbo.Roles WHERE name='contributor'), N'Công Đoàn Khoa CNTT');
END;
GO

-- Seed Categories
IF NOT EXISTS (SELECT * FROM dbo.Categories WHERE slug = 'tin-tuc')
BEGIN
    INSERT INTO dbo.Categories (name, slug, description) VALUES
    (N'Tin Tức', 'tin-tuc', N'Tin tức hoạt động công đoàn trường'),
    (N'Phúc Lợi Đoàn Viên', 'phuc-loi-doan-vien', N'Chính sách và chương trình chăm lo đời sống đoàn viên'),
    (N'Giới Thiệu', 'gioi-thieu', N'Tổng quan, lịch sử và sứ mệnh Công đoàn TDMU'),
    (N'Cơ Cấu Tổ Chức', 'co-cau-to-chuc', N'Ban Thường vụ, Ban Chấp hành, Ủy ban Kiểm tra'),
    (N'Văn Bản', 'van-ban', N'Công văn chỉ đạo, kế hoạch công tác và văn bản luật'),
    (N'Biểu Mẫu', 'bieu-mau', N'Danh mục biểu mẫu thủ tục tải về'),
    (N'Liên Hệ', 'lien-he', N'Thông tin liên hệ và tiếp nhận đóng góp ý kiến');
END;
GO

-- Seed Official Articles
IF NOT EXISTS (SELECT * FROM dbo.Articles WHERE id = 101)
BEGIN
    DECLARE @catTinTuc INT = (SELECT id FROM dbo.Categories WHERE slug = 'tin-tuc');
    DECLARE @catPhucLoi INT = (SELECT id FROM dbo.Categories WHERE slug = 'phuc-loi-doan-vien');
    DECLARE @authorAdmin INT = (SELECT id FROM dbo.Users WHERE email = 'admin@tdmu.edu.vn');

    INSERT INTO dbo.Articles (id, title, slug, categoryId, authorId, summary, content, image, status, isAiGenerated, viewsCount, likesCount, sharesCount, createdAt)
    VALUES
    (101, N'Tọa đàm ''Dinh dưỡng lành mạnh vì sức khỏe gia đình'' lan tỏa giá trị xây dựng mái ấm hạnh phúc',
     'toa-dam-dinh-duong-lanh-manh',
     @catTinTuc, @authorAdmin,
     N'Hướng tới kỷ niệm 25 năm Ngày Gia đình Việt Nam, Công đoàn Trường Đại học Thủ Dầu Một đã tổ chức tọa đàm chuyên đề nâng cao nhận thức về dinh dưỡng khoa học.',
     N'<h2>I. KHAI MẠC TỌA ĐÀM CHÀO MỪNG NGÀY GIA ĐÌNH VIỆT NAM</h2><p>Ngày 26/6/2026, Ban Thường vụ Công đoàn Trường Đại học Thủ Dầu Một (TDMU) đã tổ chức tọa đàm với chủ đề <em>“Dinh dưỡng lành mạnh vì sức khỏe gia đình”</em>.</p><div class="journal-contact-card">📌 <strong>Liên hệ:</strong> Văn phòng Công đoàn TDMU | Hotline: (0274) 3815 184</div>',
     'https://congdoan.tdmu.edu.vn/img/ckeditor/Images/56c791f5-e957-413b-a3c2-040251ce7d02.jpg',
     'published', 0, 142, 45, 12, '2026-06-26 12:56'),

    (102, N'Kết quả thực hiện chăm lo phúc lợi cho đoàn viên, người lao động TDMU',
     'ket-qua-thuc-hien-cham-lo-phuc-loi-cho-doan-vien',
     @catPhucLoi, @authorAdmin,
     N'Báo cáo tổng kết công tác chăm lo đời sống, trao quà hỗ trợ và ký kết hợp tác phúc lợi dành riêng cho cán bộ, giảng viên và người lao động TDMU.',
     N'<h2>I. CHĂM LO PHÚC LỢI THÔNG QUA XÂY DỰNG CHÍNH SÁCH</h2><p>Công đoàn TDMU luôn chú trọng đàm phán, mở rộng đối tác phúc lợi mang lại các gói ưu đãi y tế, bảo hiểm và hỗ trợ vay vốn mua sắm cho đoàn viên nhà trường.</p><h2>II. CÁC CHƯƠNG TRÌNH PHÚC LỢI ĐỔI MỚI</h2><p>Thường xuyên tổ chức các đợt trợ cấp cho đoàn viên có hoàn cảnh khó khăn, tuyên dương con cán bộ đạt thành tích xuất sắc và trao vé xe sum vầy dịp Tết.</p><div class="journal-contact-card">📌 <strong>Ban Chăm Lo Đời Sống Đoàn Viên TDMU</strong><br>📞 Hotline: (0274) 3815 184 | Email: congdoan@tdmu.edu.vn</div>',
     'https://congdoan.tdmu.edu.vn/img/ckeditor/Images/23-07-2026-2-07-03-CHScreenshot 2026-07-23 134438.jpg',
     'published', 0, 2607, 320, 85, '2026-07-07 20:57');

    -- Seed Article Versions
    INSERT INTO dbo.ArticleVersions (articleId, versionNumber, title, content, createdBy, changeType, isAiGenerated, aiProvider, aiModel, aiPrompt)
    VALUES
    (101, 1, N'Tọa đàm ''Dinh dưỡng lành mạnh vì sức khỏe gia đình''', N'<p>Bản thảo ban đầu...</p>', @authorAdmin, 'EDITOR_EDIT', 0, NULL, NULL, NULL),
    (101, 2, N'Tọa đàm ''Dinh dưỡng lành mạnh vì sức khỏe gia đình'' lan tỏa giá trị xây dựng mái ấm hạnh phúc', N'<h2>I. KHAI MẠC TỌA ĐÀM</h2><p>Nội dung hoàn thiện...</p>', @authorAdmin, 'AI_EXPAND', 1, 'Google Gemini', 'gemini-2.5-flash', N'Mở rộng bài viết theo phong cách báo chí');
END;
GO
