# WEBSITE TRUYỀN THÔNG CÔNG ĐOÀN TDMU TÍCH HỢP AI (ĐỀ TÀI SỐ 8)
# TDMU trade union media CMS with AI-powered content generation

> **Dự án Đồ Án Cơ Sở Ngành - Viện Công Nghệ Số (Trường Đại Học Thủ Dầu Một)**

---

## 📌 GIỚI THIỆU HỆ THỐNG
Hệ thống là giải pháp chuyển đổi số toàn diện hỗ trợ **Công đoàn Trường Đại học Thủ Dầu Một (TDMU)** trong công tác quản lý và tự động hóa truyền thông, giải quyết triệt để vấn đề thời gian cho đội ngũ cán bộ kiêm nhiệm.

### 🌟 6 MODULE CHỨC NĂNG CỐT LÕI (ĐÃ XÂY DỰNG ĐẦY ĐỦ):
1. **Quản lý Tài khoản & Phân quyền (Auth & Roles):** Phân cấp 3 nhóm quyền chính: *Admin (Quản trị viên)*, *Editor (Biên tập viên)*, *Contributor (Cộng tác viên)*.
2. **Quản trị Nội dung Truyền thông (CMS):** Tạo, sửa, xóa, duyệt bài và phân loại theo danh mục (*Phong trào thể thao, Thông báo chỉ đạo, Quỹ công đoàn, Khen thưởng*).
3. **Trợ lý AI Tạo Nội Dung (AI Content Creator):** Nhập gợi ý (Prompt) -> AI tự động sinh bài viết hoàn chỉnh, gợi ý 3 tiêu đề hấp dẫn, sinh tóm tắt 50 từ và tạo Prompt ảnh minh họa.
4. **Quản lý Lịch Đăng Bài (Schedule & Cronjob):** Hẹn ngày/giờ cố định để hệ thống xuất bản bài viết tự động.
5. **Tích hợp Fanpage Facebook (Social Publish API):** Đẩy bài viết tự động từ Web sang Fanpage Facebook qua Facebook Graph API.
6. **Theo dõi & Thống kê Hiệu quả (Analytics Dashboard):** Bảng tổng quan lượt xem (views), lượt tương tác (likes/shares), danh sách bài viết nổi bật.

---

## 🛠️ CÔNG NGHỆ SỬ DỤNG
- **Backend:** PHP 8.2 + **Laravel 11** (Framework PHP thuần, đa nền tảng macOS/Windows/Linux)
- **Database:** **MySQL 8.0** 
- **Frontend:** HTML / CSS / Vanilla JavaScript (tĩnh, đặt trong `public/`)
- **AI:** Google **Gemini API** (`gemini-2.5-flash`) với **Engine NLP nội bộ Offline Fallback** (không cần API Key vẫn hoạt động)
- **Triển khai (Cross-platform):**
  - Chạy trực tiếp bằng `php artisan serve`
  - Hoặc dùng **Docker Compose** (`php:8.2-apache` + `mysql:8.0`)

---

## 🚀 HƯỚNG DẪN CÀI ĐẶT & CHẠY ỨNG DỤNG

### ⚙️ Yêu cầu môi trường
- **PHP** ≥ 8.2 (có extension: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`)
- **Composer**
- **MySQL** 8.0
- *(Tùy chọn)* **Docker** nếu chạy bằng container

### Bước 0: Cài đặt dependencies (chỉ lần đầu)

```bash
composer install
```

### Bước 1: Cấu hình môi trường

```bash
cp .env.example .env
php artisan key:generate
```

> **Cấu hình MySQL:** Sửa file `.env` để khớp với MySQL của bạn (password mặc định để trống hoặc theo cài đặt):
> ```
> DB_CONNECTION=mysql
> DB_HOST=127.0.0.1
> DB_PORT=3306
> DB_DATABASE=tdmu_congdoan
> DB_USERNAME=root
> DB_PASSWORD=
> ```
>
> **Cấu hình AI (tùy chọn):** Gán `GEMINI_API_KEY=<key>` để dùng Gemini thật; nếu để trống, hệ thống tự động chuyển sang **Engine NLP Offline Fallback** (vẫn sinh bài viết & trợ lý chat được).

### Bước 2: Tạo database & migration
Tạo database `tdmu_congdoan` trong MySQL rồi chạy:

```bash
php artisan migrate --seed   # tạo bảng + nạp dữ liệu mẫu
```

### Bước 3: Khởi động Server (Cách 1 – artisan serve, khuyên dùng)

```bash
php artisan serve
```

### Bước 3 (ALT): Khởi động bằng Docker Compose (Cách 2)

Docker Compose sẽ chạy cả app (Apache+PHP) lẫn database MySQL trong container:

```bash
docker compose up --build
```

> Container DB dùng `MYSQL_ROOT_PASSWORD=root`, tạo sẵn database `tdmu_congdoan`.

### Bước 4: Truy cập Giao diện Web
* **Trang Chủ Dành Cho Đoàn Viên / Người Đọc:** `http://localhost:8000`
* **Trang Quản Trị Cán Bộ CMS & Trợ Lý AI:** `http://localhost:8000/admin.html`
* **Kiểm tra sức khỏe (health check):** `http://localhost:8000/up`

> 💡 **Ghi chú port:** Ở Cách 1 (`artisan serve`) mặc định là cổng **8000**; nếu dùng Docker Compose, cổng host cũng là **8000**. Nếu muốn đổi cổng: `php artisan serve --port=XXXX`.

---

## 🔌 DANH SÁCH API (REST, prefix `/api`)

| Phương thức | Endpoint | Chức năng |
|---|---|---|
| GET | `/api/articles` | Danh sách bài viết |
| GET | `/api/articles/{id}` | Chi tiết bài viết |
| POST | `/api/articles` | Tạo bài viết |
| PUT | `/api/articles/{id}` | Cập nhật bài viết |
| DELETE | `/api/articles/{id}` | Xóa bài viết |
| POST | `/api/articles/{id}/approve` | Duyệt bài |
| POST | `/api/articles/{id}/like` | Tương tác thích |
| POST | `/api/articles/{id}/comments` | Thêm bình luận |
| GET | `/api/users` | Danh sách tài khoản |
| POST | `/api/users` | Tạo tài khoản |
| DELETE | `/api/users/{id}` | Xóa tài khoản |
| GET | `/api/events` | Danh sách sự kiện |
| POST | `/api/events` | Tạo sự kiện |
| DELETE | `/api/events/{id}` | Xóa sự kiện |
| GET | `/api/media` | Danh sách media |
| POST | `/api/media/upload` | Upload media |
| DELETE | `/api/media/{id}` | Xóa media |
| GET | `/api/comments` | Danh sách bình luận (inbox) |
| DELETE | `/api/comments/{id}` | Xóa bình luận |
| GET | `/api/analytics` | Thống kê tổng quan |
| GET | `/api/audits` | Nhật ký hoạt động |
| GET | `/api/inbox/comments` | Hộp thư bình luận |
| POST | `/api/facebook/publish` | Đẩy bài lên Fanpage |
| POST | `/api/ai/generate` | AI sinh bài viết |
| POST | `/api/ai/quality-check` | AI kiểm tra chất lượng |
| POST | `/api/ai/chat` | Trợ lý AI chat |
| POST | `/api/ai/floating-command` | Lệnh AI nhanh |
| POST | `/api/ai/repurpose` | Chuyển đổi nội dung đa nền tảng |
| POST | `/api/ai/event-plan-generator` | AI lập kế hoạch sự kiện |
| POST | `/api/ai/image-prompt-generator` | AI sinh prompt ảnh |

Xem đầy đủ routes: `php artisan route:list`

---

## 📂 CẤU TRÚC THƯ MỤC DỰ ÁN
```text
tdmu-congdoan-app/
├── app/
│   ├── Http/Controllers/   # 10 Controllers (Article, AiStudio, User, Event,
│   │                       #   Media, Comment, Analytics, Audit, Inbox, Facebook)
│   ├── Models/             # 10 Eloquent Models (Role, User, Category, Article,
│   │                       #   ArticleVersion, ArticleAudit, Audit, Comment, Event, Media)
│   └── Services/
│       └── GeminiAiService.php   # AI Gemini + Fallback NLP nội bộ
├── bootstrap/app.php       # Cấu hình khởi tạo Laravel 11
├── config/                 # Cấu hình ứng dụng (database, ...)
├── database/
│   ├── migrations/         # 11 bảng (roles, users, articles, events, media, ...)
│   └── seeders/            # Dữ liệu mẫu
├── public/
│   ├── index.html          # Trang chủ Portal truyền thông Công đoàn TDMU
│   ├── admin.html          # Portal quản trị CMS, AI Creator, Schedule & Fanpage
│   ├── index.php           # Front controller Laravel
│   ├── css/
│   │   └── style.css       # Hệ thống Design System chuẩn nhận diện TDMU
│   └── js/
│       ├── api.js          # Tầng gọi REST API (29 endpoints)
│       ├── app.js          # Logic xử lý giao diện người đọc
│       └── admin.js        # Logic quản trị, AI, duyệt bài & FB Push
├── routes/
│   ├── web.php             # Route trang tĩnh
│   └── api.php             # 29 route API REST
├── legacy/                 # (Tham khảo) Backend Node.js cũ
├── .env.example            # Mẫu cấu hình môi trường
├── composer.json           # Danh sách thư viện PHP
├── docker-compose.yml      # Chạy app + MySQL bằng container
└── README.md               # Hướng dẫn chi tiết
```
