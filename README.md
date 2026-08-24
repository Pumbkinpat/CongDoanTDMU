# WEBSITE TRUYỀN THÔNG CÔNG ĐOÀN TDMU TÍCH HỢP AI (ĐỀ TÀI SỐ 8)

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

## 🛠️ HƯỚNG DẪN CÀI ĐẶT & CHẠY ỨNG DỤNG LOCAL

### Bước 1: Khởi động Server
Mở terminal trong thư mục `tdmu-congdoan-app` và chạy lệnh:

```bash
npm start
```

### Bước 2: Truy cập Giao diện Web
* **Trang Chủ Dành Cho Đoàn Viên / Người Đọc:** `http://localhost:3000`
* **Trang Quản Trị Cán Bộ CMS & Trợ Lý AI:** `http://localhost:3000/admin.html`

---

## 📂 CẤU TRÚC THƯ MỤC DỰ ÁN
```text
tdmu-congdoan-app/
├── public/
│   ├── index.html       # Trang chủ Portal truyền thông Công đoàn TDMU
│   ├── admin.html       # Portal quản trị CMS, AI Creator, Schedule & Fanpage
│   ├── css/
│   │   └── style.css    # Hệ thống Design System chuẩn nhận diện TDMU
│   ├── js/
│   │   ├── app.js       # Logic xử lý giao diện người đọc
│   │   └── admin.js     # Logic xử lý quản trị, gọi AI API, duyệt bài & FB Push
│   └── images/          # Hình ảnh banner & sự kiện TDMU
├── server/
│   └── server.js        # Backend API Server Node.js / Express
├── .env                 # Cấu hình API Key (OpenAI/Gemini/Facebook)
├── package.json         # Danh sách thư viện mã nguồn
└── README.md            # Hướng dẫn chi tiết
```
