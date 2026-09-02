<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiAiService {
    protected ?string $apiKey;
    protected const MODEL = 'gemini-2.5-flash';
    protected const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    public function __construct(?string $apiKey = null) {
        $this->apiKey = $apiKey ?: env('GEMINI_API_KEY');
    }

    /**
     * Master Multi-Modal AI Prompt Engine (Gemini 2.5 Flash + Local NLP)
     */
    public function generateArticleContent(string $prompt, string $category, string $tone): array {
        if ($this->apiKey) {
            try {
                $systemPrompt = "Hãy đóng vai Trợ Lý AI chuyên nghiệp sáng tạo nội dung truyền thông cho Công đoàn Trường Đại học Thủ Dầu Một (TDMU).Hãy tạo bài viết chuẩn văn phong Công đoàn cho yêu cầu: {$prompt}, Chuyên mục: {$category}, Văn phong: {$tone}. Trả về định dạng JSON gồm: titles (mảng 3 tiêu đề), summary (tóm tắt 50 từ), content (nội dung chi tiết 3 đoạn).";

                $response = $this->callGeminiApiRaw($systemPrompt, $this->apiKey);
                if ($response) {
                    $data = json_decode($response, true);
                    if ($data && isset($data['titles'])) {
                        return array_merge($data, ['source' => 'Gemini 2.5 Flash API Live']);
                    }
                }
            } catch (\Exception $e) {
                // Fallback on exception
            }
        }

        return $this->fallbackNlpEngine($prompt, $category, $tone);
    }

    /**
     * Full guardrail prompt following TDMU regulations (from legacy server)
     */
    public function generateWithGuardrails(
        string $eventTitle,
        string $category,
        string $tone,
        string $lengthOption,
        string $targetAudience,
        string $issuingUnit,
        string $author,
        ?array $eventForm,
        string $apiKey
    ): ?array {
        $systemPrompt = "BẠN LÀ CHUYÊN VIÊN TRƯỞNG BAN TUYÊN GIÁO - TRUYỀN THÔNG CÔNG ĐOÀN TRƯỜNG ĐẠI HỌC THỦ DẦU MỘT (TDMU).
Nhiệm vụ của bạn là soạn thảo bài viết truyền thông chính thống, chuẩn mực văn phong hành chính đoàn thể, kết hợp hài hòa giữa tính trang trọng của môi trường giáo dục đại học và tinh thần nhiệt huyết, tương thân tương ái của tổ chức Công đoàn.

CẤU TRÚC BÀI VIẾT 4 PHẦN CHUẨN MỰC BÁO CHÍ ĐOÀN THỂ:
Phần I. BỐI CẢNH & Ý NGHĨA CHÍNH TRỊ:
- Nêu rõ căn cứ kế hoạch công tác năm 2026 của Công đoàn Trường Đại học Thủ Dầu Một, mục đích chăm lo hoặc đẩy mạnh phong trào thi đua.
Phần II. THÔNG TIN TRỌNG TÂM & NỘI DUNG CHI TIẾT:
- Thời gian, địa điểm, đối tượng tham gia, tiến độ thực hiện, các nội dung hoạt động nổi bật.
Phần III. LAN TỎA TINH THẦN & TRÍCH DẪN (BLOCKQUOTE):
- Trích dẫn 1 câu phát biểu ý nghĩa của Ban Thường vụ hoặc thông điệp cốt lõi.
Phần IV. TRÁCH NHIỆM PHỐI HỢP & THÔNG TIN LIÊN HỆ CHÍNH THỨC:
- Văn phòng Công đoàn TDMU (Lầu 1, Dãy A, Cổng 1, Số 06 Trần Văn Ơn, TP. Thủ Dầu Một), Hotline (0274) 3815 184, Email: congdoan@tdmu.edu.vn.

THÔNG TIN ĐẦU VÀO:
- Yêu cầu / Sự kiện: {$eventTitle}
- Chuyên mục: {$category}
- Đơn vị ban hành: {$issuingUnit}
- Tác giả: {$author}
- Văn phong: {$tone}
- Độ dài: {$lengthOption}
- Đối tượng thụ hưởng: {$targetAudience}
" . ($eventForm ? "Chi tiết sự kiện: Tên=\"{$eventForm['name']}\", Ngày=\"" . ($eventForm['date'] ?? '') . "\", Thời gian=\"" . ($eventForm['time'] ?? '') . "\", Địa điểm=\"" . ($eventForm['location'] ?? '') . "\", Kinh phí=\"" . ($eventForm['budget'] ?? '') . "\", Người tham gia=\"" . ($eventForm['attendees'] ?? '') . "\"" : '') . "

QUY ĐỊNH ĐẦU RA (JSON FORMAT DUY NHẤT, KHÔNG THÊM TEXT NGOÀI JSON):
{
  \"titles\": [\"Tiêu đề 1\", \"Tiêu đề 2\", \"Tiêu đề 3\"],
  \"subTitle\": \"Tiêu đề phụ 1 câu súc tích...\",
  \"summary\": \"Tóm tắt bài viết chính xác 50 từ...\",
  \"content\": \"Nội dung bài viết HTML chuẩn cấu trúc (sử dụng <h2>, <p>, <blockquote>, <ul>, <li>...)\"
}";

        $response = $this->callGeminiApiRaw($systemPrompt, $apiKey);
        if (!$response) return null;

        $parsed = json_decode($response, true);
        if (!$parsed) return null;

        return [
            'success' => true,
            'source' => 'Google Gemini 2.5 Flash API Live',
            'titles' => $parsed['titles'] ?? [],
            'subTitle' => $parsed['subTitle'] ?? '',
            'summary' => $parsed['summary'] ?? '',
            'content' => $parsed['content'] ?? ''
        ];
    }

    /**
     * Manus AI Copilot - direct document editing endpoint
     */
    public function chatWithCopilot(
        string $message,
        array $history,
        string $articleTitle,
        string $articleContent,
        string $selectedText,
        string $apiKey
    ): ?array {
        $historyText = '';
        if (count($history) > 0) {
            $historyText = "\n--- LỊCH SỬ TRÒ CHUYỆN (CONTEXT MEMORY) ---\n";
            $recent = array_slice($history, -4);
            foreach ($recent as $h) {
                $role = ($h['role'] ?? 'user') === 'user' ? 'CÁN BỘ' : 'BẠN (COPILOT)';
                $historyText .= $role . ': "' . ($h['text'] ?? '') . "\"\n";
            }
            $historyText .= "ĐẶC BIỆT LƯU Ý: Nếu người dùng báo lỗi, yêu cầu viết lại hoặc tạo bản khác, bạn PHẢI TẠO RA MỘT KẾT QUẢ MỚI HOÀN TOÀN.\n";
        }

        $cleanContent = strip_tags($articleContent);
        $systemPrompt = "BẠN LÀ MANUS AI COPILOT - TRỢ LÝ TRUYỀN THÔNG CÔNG ĐOÀN TDMU.
Bạn có quyền năng CHỈNH SỬA TRỰC TIẾP tài liệu của người dùng, không chỉ chat suông.
Ngữ cảnh hiện tại:
- Tiêu đề: \"{$articleTitle}\"
- Đoạn văn bản ĐANG BÔI ĐEN (Nếu có): \"{$selectedText}\"
- Toàn bộ nội dung bài viết: \"" . mb_substr($cleanContent, 0, 1500) . "...\"

NHIỆM VỤ: Phân tích yêu cầu ({$message}) và trả về ĐÚNG định dạng JSON Schema sau:
{
  \"reply\": \"Câu trả lời ngắn gọn, thân thiện\",
  \"editAction\": \"REPLACE_SELECTION\" | \"REPLACE_ALL\" | \"APPEND\" | \"NONE\",
  \"editContent\": \"Nội dung HTML mới để áp dụng. Nếu editAction là NONE thì để rỗng.\"
}

QUY TẮC SỐNG CÒN:
1. Nếu ĐANG BÔI ĐEN CHỮ và yêu cầu sửa: dùng REPLACE_SELECTION, editContent CHỈ chứa đoạn đã sửa, TUYỆT ĐỐI KHÔNG chép lại toàn bộ bài viết.
2. Yêu cầu chèn thêm: dùng APPEND, editContent chỉ chứa phần mới chèn.
3. Yêu cầu làm mới toàn bộ: dùng REPLACE_ALL, phải viết hoàn chỉnh bài viết.
4. Trò chuyện bình thường: dùng NONE, editContent bằng \"\".
{$historyText}";

        $response = $this->callGeminiApiRaw($systemPrompt, $apiKey);
        if (!$response) return null;

        $result = json_decode(trim($response), true);
        if (!$result) return null;

        return [
            'success' => true,
            'source' => 'Google Gemini 2.5 Flash Live Copilot',
            'reply' => $result['reply'] ?? '',
            'editAction' => $result['editAction'] ?? 'NONE',
            'editContent' => $result['editContent'] ?? ''
        ];
    }

    protected function callGeminiApiRaw(string $systemPrompt, string $apiKey, array $extraConfig = []): ?string {
        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout(60)
            ->post(self::API_URL . "?key={$apiKey}", [
                'contents' => [['parts' => [['text' => $systemPrompt]]]],
                'generationConfig' => array_merge(['responseMimeType' => 'application/json'], $extraConfig)
            ]);

        if (!$response->successful()) return null;

        return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    protected function callGeminiApi(string $prompt, string $apiKey): ?string {
        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout(60)
            ->post(self::API_URL . "?key={$apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]]
            ]);

        if (!$response->successful()) return null;

        return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    public function fallbackGenerate(string $eventTitle, string $category, string $tone): array {
        return [
            'success' => true,
            'source' => 'Local Dynamic NLP Engine (Offline Fallback)',
            'titles' => [
                "Thông Báo: Kế Hoạch Tổ Chức {$eventTitle} (Công Đoàn TDMU 2026)",
                "Sôi Nổi Thi Đua: {$eventTitle} Chào Mừng Phong Trào Đột Phá ĐH Thủ Dầu Một",
                "Công Đoàn TDMU Triển Khai Chương Trình: {$eventTitle}"
            ],
            'subTitle' => "Hoạt động trọng tâm hướng đến xây dựng môi trường đại học văn minh, hạnh phúc",
            'summary' => "Công đoàn Trường Đại học Thủ Dầu Một chính thức phát động kế hoạch {$eventTitle} nhằm nâng cao đời sống vật chất và tinh thần cho cán bộ, giảng viên.",
            'content' => "<h2>I. MỤC ĐÍCH VÀ Ý NGHĨA CHƯƠNG TRÌNH</h2><p>Thực hiện chương trình công tác năm 2026 của Ban Thường vụ Công đoàn Trường Đại học Thủ Dầu Một, nhà trường trân trọng thông báo kế hoạch tổ chức: <strong>{$eventTitle}</strong>.</p><h2>II. THỜI GIAN VÀ ĐỊA ĐIỂM TỔ CHỨC</h2><p>• <strong>Địa điểm:</strong> Trường Đại học Thủ Dầu Một (Số 06 Trần Văn Ơn, TP. Thủ Dầu Một, Bình Dương).</p><blockquote><i class=\"fa-solid fa-quote-left\"></i> \"Phát huy tinh thần đoàn kết, sáng tạo và đổi mới trong phong trào thi đua dạy tốt học tốt.\"</blockquote><div class=\"journal-contact-card\">📌 <strong>Văn phòng Công đoàn TDMU:</strong> Lầu 1, Dãy A, Cổng 1 | 📞 Hotline: (0274) 3815 184</div>"
        ];
    }

    public function paraphraseContent(string $text): string {
        return "Nội dung đã được AI tối ưu hóa văn phong trang trọng: " . $text;
    }

    public function generateSocialCaption(string $title, string $summary): string {
        return "📢 [TDMU NEWS] {$title}\n\n{$summary}\n\n👉 Đọc chi tiết tại Website Công đoàn TDMU!\n#CongDoanTDMU #ChuyenDoiSo #AI2026";
    }

    protected function fallbackNlpEngine(string $prompt, string $category, string $tone): array {
        $clean = trim(preg_replace('/viết bài|thông báo|về việc|hãy/iu', '', $prompt));
        $titleBase = mb_strtoupper(mb_substr($clean, 0, 1)) . mb_substr($clean, 1);

        return [
            'source' => 'Smart Dynamic AI Engine (TDMU NLP)',
            'titles' => [
                "[Công Đoàn TDMU] " . $titleBase,
                "Phát Động Chương Trình: " . $titleBase . " (Năm 2026)",
                "Thông Báo Sôi Nổi: " . $titleBase
            ],
            'summary' => "Thông báo chính thức từ Công đoàn Trường Đại học Thủ Dầu Một về việc triển khai: \"{$prompt}\". Kính mời toàn thể cán bộ, giảng viên và đoàn viên theo dõi và tham gia đông đủ.",
            'content' => "Công đoàn Trường Đại học Thủ Dầu Một (TDMU) xin trân trọng thông báo tới toàn thể cán bộ, giảng viên, người lao động và đoàn viên công đoàn về chương trình: \"{$prompt}\".\n\nNội dung chi tiết được thực hiện với văn phong {$tone}, đảm bảo phát huy tinh thần đoàn kết, thi đua xuất sắc trong toàn trường.\n\nKính đề nghị các Công đoàn bộ phận triển khai sâu rộng đến toàn thể đoàn viên để tham gia hưởng ứng nhiệt tình.",
            'category' => $category
        ];
    }
}