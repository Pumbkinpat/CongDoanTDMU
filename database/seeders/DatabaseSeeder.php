<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use App\Models\Category;
use App\Models\Article;
use App\Models\Event;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder {
    public function run(): void {
        // Roles
        $adminRole = Role::create(['name' => 'admin', 'display_name' => 'Quản Trị Viên (Admin)', 'description' => 'Toàn quyền quản trị']);
        $editorRole = Role::create(['name' => 'editor', 'display_name' => 'Biên Tập Viên (Editor)', 'description' => 'Duyệt bài và chỉnh sửa']);
        $contributorRole = Role::create(['name' => 'contributor', 'display_name' => 'Cộng Tác Viên (Contributor)', 'description' => 'Soạn bài gửi chờ duyệt']);

        // Users
        $admin = User::create([
            'name' => 'Thầy Nguyễn Văn A',
            'email' => 'nguyenvana@tdmu.edu.vn',
            'password' => bcrypt('123456'),
            'role_id' => $adminRole->id,
            'department' => 'Ban Chấp Hành Công Đoàn TDMU'
        ]);

        // Categories
        $cat1 = Category::create(['name' => 'Phong Trào Thể Thao', 'slug' => 'phong-trao-the-thao']);
        $cat2 = Category::create(['name' => 'Thông Báo Chỉ Đạo', 'slug' => 'thong-bao-chi-dao']);
        $cat3 = Category::create(['name' => 'Quỹ Công Đoàn', 'slug' => 'quy-cong-doan']);
        $cat4 = Category::create(['name' => 'Khen Thưởng & Chăm Lo', 'slug' => 'khen-thuong-cham-lo']);

        // Articles
        Article::create([
            'title' => 'Công Đoàn TDMU Đẩy Mạnh Ứng Dụng Trí Tuệ Nhân Tạo (AI) Trong Sáng Tạo Nội Dung Truyền Thông 2026',
            'slug' => 'cong-doan-tdmu-day-manh-ung-dung-ai-2026',
            'category_id' => $cat2->id,
            'author_id' => $admin->id,
            'summary' => 'Công đoàn Trường Đại học Thủ Dầu Một chính thức đưa vào sử dụng trợ lý AI hỗ trợ tự động hóa khâu biên soạn thông báo.',
            'content' => 'Nhằm nâng cao hiệu quả công tác truyền thông và giảm bớt thời gian biên soạn cho đội ngũ cán bộ kiêm nhiệm, Ban Thường vụ Công đoàn TDMU đã triển khai ứng dụng AI.',
            'status' => 'published',
            'is_ai_generated' => true,
            'views_count' => 1240,
            'likes_count' => 312,
            'published_at' => now()
        ]);

        // Events
        Event::create([
            'title' => 'Giải Bóng Chuyền Nam/Nữ Công Đoàn TDMU Chào Mừng Ngày 26/03',
            'location' => 'Nhà Thi Đấu Đa Năng TDMU',
            'start_time' => '2026-03-26 08:00:00',
            'description' => 'Giải đấu thể thao thường niên dành cho cán bộ giảng viên TDMU.',
            'status' => 'upcoming'
        ]);
    }
}
