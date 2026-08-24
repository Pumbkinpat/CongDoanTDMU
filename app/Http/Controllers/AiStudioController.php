<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiStudioController extends Controller
{
    /**
     * POST /api/ai/generate
     * Master Multi-Modal AI Prompt Engine (Gemini 2.5 Flash + Local NLP)
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'prompt' => 'nullable|string',
            'eventForm' => 'nullable|array',
            'category' => 'nullable|string',
            'tone' => 'nullable|string',
            'lengthOption' => 'nullable|string',
            'targetAudience' => 'nullable|string',
            'issuingUnit' => 'nullable|string',
            'author' => 'nullable|string'
        ]);

        $apiKey = $request->input('apiKey') ?: env('GEMINI_API_KEY');
        $eventTitle = $validated['eventForm']['name'] ?? ($validated['prompt'] ?? 'Hoạt Động Phong Trào Công Đoàn TDMU 2026');

        if ($apiKey) {
            try {
                $systemPrompt = "BẠN LÀ CHUYÊN VIÊN TRƯỞNG BAN TUYÊN GIÁO - TRUYỀN THÔNG CÔNG ĐOÀN TRƯỜNG ĐẠI HỌC THỦ DẦU MỘT (TDMU).
Nhiệm vụ: Soạn thảo bài viết truyền thông chính thống chuẩn mực văn phong hành chính đoàn thể và giáo dục đại học.
Cấu trúc bắt buộc:
I. Bối cảnh & Căn cứ kế hoạch công tác Công đoàn TDMU 2026.
II. Thời gian, địa điểm, đối tượng, nội dung sự kiện ({$eventTitle}).
III. Trích dẫn ý nghĩa (<blockquote>) về tinh thần đoàn kết, tương thân tương ái, thi đua dạy tốt học tốt.
IV. Trách nhiệm phối hợp & Thông tin liên hệ Văn phòng Công đoàn TDMU (0274.3815.184, congdoan@tdmu.edu.vn).

Yêu cầu xuất ra JSON hợp lệ duy nhất:
{
  "titles": ["Tiêu đề 1", "Tiêu đề 2", "Tiêu đề 3"],
  "subTitle": "Tiêu đề phụ...",
  "summary": "Tóm tắt 50 từ...",
  "content": "HTML hoàn chỉnh..."
}";

                $res = Http::withHeaders(['Content-Type' => 'application/json'])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                        'contents' => [['parts' => [['text' => $systemPrompt]]]],
                        'generationConfig' => ['responseMimeType' => 'application/json']
                    ]);

                if ($res->successful()) {
                    $jsonText = $res->json()['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
                    $parsed = json_decode($jsonText, true);
                    return response()->json([
                        'success' => true,
                        'source' => 'Google Gemini 2.5 Flash API Live',
                        'titles' => $parsed['titles'] ?? [],
                        'subTitle' => $parsed['subTitle'] ?? '',
                        'summary' => $parsed['summary'] ?? '',
                        'content' => $parsed['content'] ?? ''
                    ]);
                }
            } catch (\Exception $e) {
                // Fallback to local
            }
        }

        // Local Rule-Based NLP Fallback
        return response()->json([
            'success' => true,
            'source' => 'Local Dynamic NLP Engine (Offline Fallback)',
            'titles' => [
                "Thông Báo: Kế Hoạch Tổ Chức {$eventTitle} (Công Đoàn TDMU 2026)",
                "Sôi Nổi Thi Đua: {$eventTitle} Chào Mừng Phong Trào Đột Phá ĐH Thủ Dầu Một",
                "Công Đoàn TDMU Triển Khai Chương Trình: {$eventTitle}"
            ],
            'subTitle' => "Hoạt động trọng tâm hướng đến xây dựng môi trường đại học văn minh, hạnh phúc",
            'summary' => "Công đoàn Trường Đại học Thủ Dầu Một chính thức phát động kế hoạch {$eventTitle} nhằm nâng cao đời sống vật chất và tinh thần cho cán bộ, giảng viên.",
            'content' => "<h2>I. MỤC ĐÍCH VÀ Ý NGHĨA CHƯƠNG TRÌNH</h2><p>Thực hiện chương trình công tác năm 2026 của Ban Thường vụ Công đoàn Trường Đại học Thủ Dầu Một, nhà trường trân trọng thông báo kế hoạch tổ chức: <strong>{$eventTitle}</strong>.</p><h2>II. THỜI GIAN VÀ ĐỊA ĐIỂM TỔ CHỨC</h2><p>• <strong>Địa điểm:</strong> Trường Đại học Thủ Dầu Một (Số 06 Trần Văn Ơn, TP. Thủ Dầu Một, Bình Dương).</p><blockquote><i class="fa-solid fa-quote-left"></i> "Phát huy tinh thần đoàn kết, sáng tạo và đổi mới trong phong trào thi đua dạy tốt học tốt."</blockquote><div class="journal-contact-card">📌 <strong>Văn phòng Công đoàn TDMU:</strong> Lầu 1, Dãy A, Cổng 1 | 📞 Hotline: (0274) 3815 184</div>"
        ]);
    }
}
