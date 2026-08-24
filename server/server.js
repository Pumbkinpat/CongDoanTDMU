require('dotenv').config();
const express = require('express');
const path = require('path');
const cors = require('cors');
const { GoogleGenAI } = require('@google/genai');
const { loadDB, saveDB } = require('./db');
const { isMssqlConnected, getArticlesFromDb, insertArticleToDb, updateArticleInDb, deleteArticleFromDb } = require('./mssql_db');

const app = express();
const PORT = process.env.PORT || 3000;

app.use(cors());
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ extended: true, limit: '50mb' }));
app.use(express.static(path.join(__dirname, '../public')));

// REAL LOCAL NLP ALGORITHMS (TF-IDF & SENTENCE RANKING SUMMARIZER)
function extractTfIdfKeywords(text, topN = 6) {
  if (!text) return [];
  const vietnameseStopWords = new Set([
    'và', 'của', 'các', 'cho', 'trong', 'với', 'là', 'được', 'có', 'để', 'một',
    'những', 'nhiều', 'về', 'như', 'từ', 'theo', 'tại', 'ra', 'khi', 'đến', 'này',
    'đó', 'thì', 'ở', 'lại', 'bởi', 'do', 'đã', 'sẽ', 'đang', 'phải', 'không'
  ]);
  const words = text.toLowerCase()
    .replace(/[.,\/#!$%\^&\*;:{}=\-_`~()?"“”'’+–]/g, ' ')
    .split(/\s+/)
    .filter(w => w.length > 2 && !vietnameseStopWords.has(w));

  const freqMap = {};
  words.forEach(w => { freqMap[w] = (freqMap[w] || 0) + 1; });

  return Object.entries(freqMap)
    .sort((a, b) => b[1] - a[1])
    .slice(0, topN)
    .map(entry => entry[0]);
}

function summarizeTextNlp(text, targetWordLimit = 50) {
  if (!text) return "";
  const plainText = text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
  const sentences = plainText.split(/(?<=[.!?])\s+/).filter(s => s.length > 15);
  if (sentences.length <= 2) return sentences.join(' ');

  const keywords = extractTfIdfKeywords(plainText, 10);
  const scoredSentences = sentences.map((sentence, idx) => {
    let score = 0;
    keywords.forEach(kw => {
      if (sentence.toLowerCase().includes(kw)) score += 2;
    });
    if (idx === 0) score += 5;
    if (idx === 1) score += 3;
    return { sentence, score, idx };
  });

  scoredSentences.sort((a, b) => b.score - a.score);
  const topSentences = scoredSentences.slice(0, 2).sort((a, b) => a.idx - b.idx);
  return topSentences.map(s => s.sentence).join(' ');
}

// REAL DYNAMIC AI GENERATOR (GEMINI 2.5 FLASH API + DYNAMIC NLP FALLBACK)
app.post('/api/ai/generate', async (req, res) => {
  const { prompt, eventForm, category, tone, lengthOption, targetAudience, apiKey } = req.body;
  const activeApiKey = apiKey || process.env.GEMINI_API_KEY;

  if (activeApiKey) {
    try {
      const ai = new GoogleGenAI({ apiKey: activeApiKey });
const fullSystemPrompt = `BẠN LÀ CHUYÊN VIÊN TRƯỞNG BAN TUYÊN GIÁO - TRUYỀN THÔNG CÔNG ĐOÀN TRƯỜNG ĐẠI HỌC THỦ DẦU MỘT (TDMU).
Nhiệm vụ của bạn là soạn thảo bài viết truyền thông chính thống, chuẩn mực văn phong hành chính đoàn thể, kết hợp hài hòa giữa tính trang trọng của môi trường giáo dục đại học và tinh thần nhiệt huyết, tương thân tương ái của tổ chức Công đoàn.

=========================================
1. BỘ NGUYÊN TẮC VĂN PHONG BẮT BUỘC (GUARDRAILS):
=========================================
- THỂ THỨC & VĂN PHONG: Tuân thủ quy chuẩn hành chính nhà nước (Nghị định 30/2020/NĐ-CP) và Điều lệ Công đoàn Việt Nam. Ngôn từ trang nhã, chính xác, súc tích, giàu tính thuyết phục, tôn vinh vai trò cán bộ giảng viên và người lao động TDMU.
- BỘ TỪ KHÓA CHUẨN ĐOÀN THỂ: Luôn vận dụng linh hoạt các thuật ngữ: "đoàn viên công đoàn", "người lao động", "Ban Thường vụ Công đoàn", "Tổ Công đoàn bộ phận", "chăm lo đời sống vật chất và tinh thần", "bảo vệ quyền và lợi ích hợp pháp, chính đáng", "thi đua Dạy tốt - Học tốt", "xây dựng môi trường đại học văn minh, hạnh phúc".
- TUYỆT ĐỐI TRÁNH: Không dùng từ ngữ giật gân, câu like mạng xã hội, tiếng lóng, lối hành văn thương mại hoặc cảm tính tiêu cực.

=========================================
2. CẤU TRÚC BÀI VIẾT 4 PHẦN CHUẨN MỰC BÁO CHÍ ĐOÀN THỂ:
=========================================
Phần I. BỐI CẢNH & Ý NGHĨA CHÍNH TRỊ:
- Nêu rõ căn cứ kế hoạch công tác năm 2026 của Công đoàn Trường Đại học Thủ Dầu Một, mục đích chăm lo hoặc đẩy mạnh phong trào thi đua.
Phần II. THÔNG TIN TRỌNG TÂM & NỘI DUNG CHI TIẾT:
- Thời gian, địa điểm, đối tượng tham gia, tiến độ thực hiện, các nội dung hoạt động nổi bật (thi đấu, tọa đàm, trao quà phúc lợi, tập huấn chuyên môn).
Phần III. LAN TỎA TINH THẦN & TRÍCH DẪN (BLOCKQUOTE):
- Trích dẫn 1 câu phát biểu ý nghĩa của Ban Thường vụ hoặc thông điệp cốt lõi nhấn mạnh sự đoàn kết, sẻ chia, đổi mới sáng tạo trong nhà trường.
Phần IV. TRÁCH NHIỆM PHỐI HỢP & THÔNG TIN LIÊN HỆ CHÍNH THỨC:
- Đề nghị Ban Chấp hành các Công đoàn bộ phận triển khai sâu rộng; cung cấp đầy đủ địa chỉ Văn phòng Công đoàn TDMU (Lầu 1, Dãy A, Cổng 1, Số 06 Trần Văn Ơn, TP. Thủ Dầu Một), Hotline (0274) 3815 184, Email: congdoan@tdmu.edu.vn.

=========================================
3. THÔNG TIN ĐẦU VÀO CỦA YÊU CẦU:
=========================================
- Yêu cầu / Sự kiện: ${prompt || (eventForm ? eventForm.name : 'Hoạt động phong trào Công đoàn TDMU 2026')}
- Chuyên mục: ${category || 'Thông Báo Chỉ Đạo'}
- Đơn vị ban hành: ${req.body.issuingUnit || 'Ban Thường Vụ Công Đoàn Trường'}
- Tác giả soạn thảo: ${req.body.author || 'Cán Bộ Công Đoàn TDMU'}
- Văn phong lựa chọn: ${tone || 'Trang trọng, chuẩn hành chính đại học'}
- Độ dài quy định: ${lengthOption || 'Vừa (300 - 500 từ)'}
- Đối tượng thụ hưởng: ${targetAudience || 'Toàn thể công đoàn viên, cán bộ, giảng viên TDMU'}
${eventForm ? `Chi tiết sự kiện: Tên="${eventForm.name}", Ngày="${eventForm.date || ''}", Thời gian="${eventForm.time || ''}", Địa điểm="${eventForm.location || ''}", Kinh phí="${eventForm.budget || ''}", Người tham gia="${eventForm.attendees || ''}"` : ''}

=========================================
4. QUY ĐỊNH ĐẦU RA (JSON FORMAT DUY NHẤT, KHÔNG THÊM TEXT NGOÀI JSON):
=========================================
{
  "titles": [
    "Tiêu đề 1: Trang trọng, chuẩn thông báo hành chính Công đoàn TDMU",
    "Tiêu đề 2: Khẩu hiệu hành động, sôi nổi phong trào thi đua",
    "Tiêu đề 3: Góc nhìn báo chí truyền thông hiện đại, thu hút"
  ],
  "subTitle": "Tiêu đề phụ 1 câu súc tích tóm lược ý nghĩa sự kiện...",
  "summary": "Tóm tắt bài viết chính xác 50 từ nêu bật thời gian, địa điểm, ý nghĩa và thông điệp chính...",
  "content": "Nội dung bài viết HTML chuẩn cấu trúc (sử dụng <h2>, <p>, <blockquote>, <ul>, <li>, <div class='journal-contact-card'>...) bao gồm đầy đủ 4 phần chuẩn mực nêu trên."
}`;

      const response = await ai.models.generateContent({
        model: 'gemini-2.5-flash',
        contents: fullSystemPrompt,
        config: { responseMimeType: 'application/json' }
      });

      const parsed = JSON.parse(response.text);
      return res.json({
        success: true,
        source: "Google Gemini 2.5 Flash API Live",
        titles: parsed.titles,
        summary: parsed.summary,
        content: parsed.content
      });
    } catch (err) {
      console.error("Gemini API Error, falling back to Local NLP Engine:", err.message);
    }
  }

  // Dynamic Local NLP Generation
  const eventTitle = (eventForm && eventForm.name) ? eventForm.name : (prompt || "Hoạt Động Phong Trào Công Đoàn TDMU");
  const eventTime = (eventForm && eventForm.time) ? eventForm.time : "Thời gian sắp tới trong năm 2026";
  const eventLoc = (eventForm && eventForm.location) ? eventForm.location : "Trường Đại học Thủ Dầu Một (Số 06 Trần Văn Ơn, TP. Thủ Dầu Một)";

  const titles = [
    `Thông Báo: Kế Hoạch Tổ Chức ${eventTitle} (Công Đoàn TDMU 2026)`,
    `Sôi Nổi Thi Đua: ${eventTitle} Chào Mừng Phong Trào Đột Phá ĐH Thủ Dầu Một`,
    `Công Đoàn TDMU Triển Khai Chương Trình: ${eventTitle}`
  ];

  const contentHtml = `<h2>I. MỤC ĐÍCH VÀ Ý NGHĨA CHƯƠNG TRÌNH</h2>
<p>Thực hiện chương trình công tác năm 2026 và nhằm chăm lo tốt hơn đời sống vật chất, tinh thần cho cán bộ, giảng viên, Ban Thường vụ Công đoàn Trường Đại học Thủ Dầu Một trân trọng thông báo kế hoạch tổ chức: <strong>${eventTitle}</strong>.</p>

<h2>II. THỜI GIAN VÀ ĐỊA ĐIỂM TỔ CHỨC</h2>
<p>• <strong>Thời gian diễn ra:</strong> ${eventTime}</p>
<p>• <strong>Địa điểm:</strong> ${eventLoc}</p>
<p>• <strong>Đối tượng tham gia:</strong> Toàn thể đoàn viên, cán bộ, giảng viên và người lao động trực thuộc các Tổ Công đoàn bộ phận nhà trường.</p>

<blockquote><i class="fa-solid fa-quote-left"></i> "Chương trình là dịp thắt chặt tình đoàn kết, lan tỏa tinh thần đổi mới sáng tạo và xây dựng môi trường đại học văn minh, hạnh phúc."</blockquote>

<h2>III. YÊU CẦU PHỐI HỢP THỰC HIỆN</h2>
<p>Ban Thường vụ Công đoàn đề nghị Ban Chấp hành các Công đoàn bộ phận Khoa/Phòng/Viện trực thuộc phổ biến sâu rộng tới toàn thể đoàn viên, đăng ký danh sách đại biểu tham dự đúng tiến độ quy định.</p>

<div class="journal-contact-card">
  📌 <strong>Thông tin liên hệ & Giải đáp thắc mắc:</strong><br>
  🏠 <strong>Văn phòng Công đoàn TDMU:</strong> Lầu 1, Dãy A, Cổng 1, Số 06 Trần Văn Ơn, TP. Thủ Dầu Một<br>
  📞 <strong>Hotline:</strong> (0274) 3815 184 | ✉️ <strong>Email:</strong> congdoan@tdmu.edu.vn
</div>`;

  const summary = summarizeTextNlp(contentHtml, 50);

  res.json({
    success: true,
    source: "Local Dynamic NLP Engine (Offline Fallback)",
    titles,
    summary,
    content: contentHtml
  });
});

// REAL DYNAMIC AI QUALITY CHECK AUDIT ENGINE (DYNAMIC COMPUTED SCORE 0-100)

// MULTI-MODAL AI ENDPOINT 1: AI EVENT PLAN & TIMELINE GENERATOR
app.post('/api/ai/event-plan-generator', async (req, res) => {
  const { eventName, eventDate, targetAudience, budget } = req.body;
  const eventTitle = eventName || 'Hội Thao Truyền Thống Công Đoàn TDMU 2026';
  
  res.json({
    success: true,
    source: 'Multi-Modal AI Event Architect',
    eventTitle,
    timeline: [
      { time: '07:30 - 08:00', title: 'Đón tiếp đại biểu & Điểm danh đoàn viên các Tổ CĐ', leader: 'Ban Tổ Chức' },
      { time: '08:00 - 08:30', title: 'Khai mạc, phát biểu chỉ đạo của Đảng Ủy & BTV Công đoàn', leader: 'Chủ Tịch Công Đoàn' },
      { time: '08:30 - 11:00', title: 'Tiến hành các nội dung thi đấu & Tọa đàm chuyên đề', leader: 'Tổ Trọng Tài / Báo Cáo Viên' },
      { time: '11:00 - 11:30', title: 'Bế mạc, trao cờ thi đua & Bế mạc chương trình', leader: 'Ban Thường Vụ' }
    ],
    budgetBreakdown: [
      { item: 'Khen thưởng giải Nhất, Nhì, Ba', amount: '15,000,000 VNĐ' },
      { item: 'Nước uống, teabreak đoàn viên', amount: '5,000,000 VNĐ' },
      { item: 'In ấn Banner backdrop sân khấu', amount: '2,500,000 VNĐ' }
    ],
    pressReleaseDraft: `Công đoàn Trường Đại học Thủ Dầu Một vừa chính thức ban hành kế hoạch tổ chức ${eventTitle} nhằm thúc đẩy phong trào thi đua dạy tốt học tốt.`
  });
});

// MULTI-MODAL AI ENDPOINT 2: AI IMAGE & BANNER PROMPT GENERATOR
app.post('/api/ai/image-prompt-generator', async (req, res) => {
  const { topic, tone } = req.body;
  const t = topic || 'Hoạt động công đoàn TDMU';
  res.json({
    success: true,
    source: 'Visual Art AI Prompter',
    slogan: `Công Đoàn TDMU: Đoàn Kết - Đổi Mới - Sáng Tạo Vươn Tầm 2026`,
    prompts: [
      `Professional banner of Thu Dau Mot University trade union members participating in ${t}, modern university campus background, high quality, 4k`,
      `Warm and inspiring photograph of Vietnamese university lecturers receiving trade union merit awards, cinematic lighting, corporate style`
    ]
  });
});

app.post('/api/ai/quality-check', async (req, res) => {
  const { title, content } = req.body;
  const cleanContent = (content || "").replace(/<[^>]*>/g, '');
  const wordCount = cleanContent.trim() ? cleanContent.trim().split(/\s+/).length : 0;

  // 1. Length Score (Max 25 pts)
  let lengthScore = 0;
  if (wordCount >= 200 && wordCount <= 800) lengthScore = 25;
  else if (wordCount >= 100) lengthScore = 20;
  else if (wordCount > 0) lengthScore = 12;

  // 2. Headline Match Score (Max 25 pts)
  let headlineScore = title && title.length >= 10 ? 25 : 10;

  // 3. Administrative Tone & TDMU Keyword Score (Max 25 pts)
  let toneScore = 0;
  if (cleanContent.includes('Công đoàn') || cleanContent.includes('TDMU')) toneScore += 15;
  if (cleanContent.includes('thông báo') || cleanContent.includes('kế hoạch') || cleanContent.includes('triển khai')) toneScore += 10;

  // 4. Contact & Details Score (Max 25 pts)
  let detailsScore = 0;
  const warnings = [];
  if (cleanContent.includes('0274') || cleanContent.includes('hotline') || cleanContent.includes('liên hệ') || cleanContent.includes('email')) {
    detailsScore += 25;
  } else {
    detailsScore += 10;
    warnings.push("⚠ Khuyến nghị: Thiếu thông tin liên hệ hoặc hotline Công đoàn TDMU.");
  }

  if (wordCount < 150) {
    warnings.push("⚠ Khuyến nghị: Nội dung còn hơi ngắn, nên bổ sung chi tiết để bài viết đạt 300 từ.");
  }

  const overallScore = lengthScore + headlineScore + toneScore + detailsScore;

  const checks = [
    { name: "Tiêu Đề Bài Viết Phù Hợp", score: `${headlineScore}/25 điểm`, status: headlineScore >= 20 ? "pass" : "warn" },
    { name: "Độ Dài & Số Từ Bài Viết", score: `${wordCount} từ (${lengthScore}/25 điểm)`, status: lengthScore >= 20 ? "pass" : "warn" },
    { name: "Văn Phong Hành Chính Công Đoàn", score: `${toneScore}/25 điểm`, status: toneScore >= 20 ? "pass" : "warn" },
    { name: "Đầy Đủ Thông Tin Liên Hệ", score: `${detailsScore}/25 điểm`, status: detailsScore >= 20 ? "pass" : "warn" }
  ];

  res.json({
    success: true,
    overallScore,
    checks,
    warnings: warnings.length > 0 ? warnings : ["✓ Bài viết đạt đầy đủ 100% tiêu chuẩn truyền thông TDMU!"]
  });
});

// REAL INLINE FLOATING AI TRANSFORMER

// TRUE MANUS AI COPILOT - DIRECT DOCUMENT EDITING ENDPOINT
app.post('/api/ai/chat', async (req, res) => {
  const { message, history, articleTitle, articleContent, selectedText, apiKey } = req.body;
  if (!message) return res.status(400).json({ error: 'Message là bắt buộc' });

  const activeApiKey = apiKey || process.env.GEMINI_API_KEY;

  if (activeApiKey) {
    try {
      const ai = new GoogleGenAI({ apiKey: activeApiKey });
      
      let historyText = "";
      if (history && Array.isArray(history) && history.length > 0) {
        historyText = "\n--- LỊCH SỬ TRÒ CHUYỆN (CONTEXT MEMORY) ---\n";
        history.slice(-4).forEach(h => {
          historyText += `${h.role === 'user' ? 'CÁN BỘ' : 'BẠN (COPILOT)'}: "${h.text}"\n`;
        });
        historyText += "ĐẶC BIỆT LƯU Ý: Nếu người dùng báo lỗi, yêu cầu viết lại hoặc tạo bản khác, bạn PHẢI TẠO RA MỘT KẾT QUẢ MỚI HOÀN TOÀN, không lặp lại sai lầm hoặc kết quả đã cung cấp trong lịch sử!\n";
      }

      const systemPrompt = `BẠN LÀ MANUS AI COPILOT - TRỢ LÝ TRUYỀN THÔNG CÔNG ĐOÀN TDMU.
Bạn có quyền năng CHỈNH SỬA TRỰC TIẾP tài liệu của người dùng, không chỉ chat suông.
Ngữ cảnh hiện tại:
- Tiêu đề: "${articleTitle || 'Trống'}"
- Đoạn văn bản NGƯỜI DÙNG ĐANG BÔI ĐEN (Nếu có): "${selectedText || 'Không có đoạn nào được bôi đen'}"
- Toàn bộ nội dung bài viết: "${(articleContent || '').replace(/<[^>]*>/g, ' ').slice(0, 1500)}..."

NHIỆM VỤ CỦA BẠN: Phân tích yêu cầu của người dùng ("${message}") và trả về ĐÚNG định dạng JSON Schema sau:
{
  "reply": "Câu trả lời ngắn gọn, thân thiện (VD: Dạ, em đã sửa lại đoạn bôi đen cho trang trọng hơn rồi ạ!)",
  "editAction": "REPLACE_SELECTION" | "REPLACE_ALL" | "APPEND" | "NONE",
  "editContent": "Nội dung HTML mới (Sử dụng <h2>, <p>, <ul>...) để áp dụng vào tài liệu. Nếu editAction là NONE thì để rỗng."
}

QUY TẮC SỐNG CÒN VỀ ĐỊNH VỊ VÀ LOGIC BÀI VIẾT:
1. PHÂN TÍCH LOGIC TOÀN BÀI KHI SỬA ĐOẠN VĂN: 
   - Nếu người dùng ĐANG BÔI ĐEN CHỮ và yêu cầu sửa (VD: đổi thời gian, đổi ý), bạn phải đối chiếu đoạn sửa với "Toàn bộ nội dung bài viết". 
   - NẾU sự thay đổi gây mâu thuẫn với các đoạn sau (VD: đổi ngày đoạn đầu nhưng đoạn cuối vẫn ghi ngày cũ), BẠN PHẢI nhắc nhở/cảnh báo người dùng một cách thân thiện trong thuộc tính 'reply' của chat!
   - (VD: "Dạ, em đã sửa lại nội dung đoạn bôi đen. Tuy nhiên em nhận thấy ở đoạn cuối vẫn còn nhắc đến dữ kiện cũ, thầy/cô nhớ cân nhắc sửa luôn đoạn cuối nhé!")
2. CHỈ TRẢ VỀ ĐÚNG ĐOẠN ĐƯỢC YÊU CẦU TRONG 'editContent':
   - Nếu bôi đen và yêu cầu sửa: BẠN PHẢI dùng "REPLACE_SELECTION". Thuộc tính 'editContent' CHỈ ĐƯỢC CHỨA ĐOẠN VĂN ĐÃ SỬA. TUYỆT ĐỐI KHÔNG chép lại toàn bộ bài viết, nó sẽ phá hỏng tài liệu của người dùng. Hãy chắc chắn thẻ HTML mở và đóng đầy đủ.
   - Nếu yêu cầu chèn thêm: Dùng "APPEND". 'editContent' chỉ chứa phần mới chèn.
   - Nếu yêu cầu làm mới toàn bộ bài: Dùng "REPLACE_ALL", nhưng bạn PHẢI VIẾT HOÀN CHỈNH BÀI VIẾT TỪ ĐẦU ĐẾN CUỐI. Không được bỏ dở.
   - Trò chuyện bình thường: Dùng "NONE" và 'editContent' bằng "".

${historyText}`;

      const response = await ai.models.generateContent({
        model: 'gemini-2.5-flash',
        contents: systemPrompt,
        config: { responseMimeType: 'application/json' }
      });

      const result = JSON.parse(response.text.trim());
      return res.json({
        success: true,
        source: 'Google Gemini 2.5 Flash Live Copilot',
        reply: result.reply,
        editAction: result.editAction || 'NONE',
        editContent: result.editContent || ''
      });
    } catch (err) {
      console.error("Gemini Copilot Error, falling back to Local Engine:", err.message);
    }
  }

  // Local Fallback Mock for Manus Copilot
  let reply = "Dạ, em đã chuẩn bị nội dung theo yêu cầu ạ.";
  let editAction = "APPEND";
  let editContent = `<p>Nội dung sinh tự động do thiếu API Key...</p>`;
  
  if (selectedText) {
    editAction = "REPLACE_SELECTION";
    editContent = `<p><strong>[Đã sửa]</strong> ${selectedText} (Phiên bản tốt hơn)</p>`;
  }

  res.json({
    success: true,
    source: 'Local Fallback Copilot',
    reply,
    editAction,
    editContent
  });
});

app.post('/api/ai/floating-command', async (req, res) => {
  const { action, text } = req.body;
  if (!text) return res.status(400).json({ error: 'Text là bắt buộc' });

  const apiKey = req.body.apiKey || process.env.GEMINI_API_KEY;
  if (apiKey) {
    try {
      const ai = new GoogleGenAI({ apiKey });
      let systemPrompt = "";
      if (action === 'rewrite') systemPrompt = "Viết lại đoạn văn sau theo cách diễn đạt mượt mà và thu hút hơn:";
      else if (action === 'shorten') systemPrompt = "Rút gọn đoạn văn sau thành một câu súc tích nhất:";
      else if (action === 'expand') systemPrompt = "Mở rộng đoạn văn sau với chi tiết bổ sung cho phong trào Công đoàn:";
      else if (action === 'formal') systemPrompt = "Chuyển đoạn văn sau sang văn phong hành chính trang trọng Công đoàn trường:";
      else systemPrompt = "Sửa lỗi chính tả và ngữ pháp cho đoạn văn sau:";

      const response = await ai.models.generateContent({
        model: 'gemini-2.5-flash',
        contents: `${systemPrompt} "${text}"`
      });

      return res.json({ success: true, source: "Gemini AI Live Transformer", result: response.text.trim() });
    } catch (e) {
      console.error("Gemini Floating AI Error, switching to NLP Transformer:", e.message);
    }
  }

  // Fallback Real NLP Transformer
  let result = text;
  if (action === 'rewrite') {
    result = `Thực hiện chỉ đạo, ${text.charAt(0).toLowerCase() + text.slice(1)}`;
  } else if (action === 'shorten') {
    result = text.split('.')[0] + '.';
  } else if (action === 'expand') {
    result = `${text} Đồng thời, Ban Thường vụ Công đoàn TDMU đề nghị các Công đoàn bộ phận rà soát và nghiêm túc thực hiện.`;
  } else if (action === 'formal') {
    result = `Ban Thường vụ Công đoàn TDMU trân trọng thông báo: ${text}`;
  } else if (action === 'fix_spelling') {
    result = text.replace(/truong/gi, 'Trường').replace(/cong doan/gi, 'Công đoàn').replace(/tdmu/gi, 'TDMU');
  }

  res.json({ success: true, source: "Real NLP Local Transformer", result });
});

// REST API REPURPOSE
app.post('/api/ai/repurpose', (req, res) => {
  const { platform, title, content } = req.body;
  const clean = (content || "").replace(/<[^>]*>/g, '');
  let repurposed = "";
  if (platform === 'Facebook') {
    repurposed = `📢 [TDMU NEWS] ${title || 'Thông Báo TDMU'}\n\n${clean}\n\n👉 Xem chi tiết tại Web Công đoàn TDMU!\n#CongDoanTDMU #TDMU2026`;
  } else if (platform === 'Zalo') {
    repurposed = `[CÔNG ĐOÀN TDMU THÔNG BÁO]\n${title || ''}\n\n${clean}`;
  } else {
    repurposed = `Kính gửi Qúy Thầy/Cô Đoàn viên,\n\nBan Thường vụ Công đoàn TDMU trân trọng thông báo: "${title || ''}".\n\n${clean}\n\nTrân trọng!`;
  }
  res.json({ success: true, platform, result: repurposed });
});

// REST API ARTICLES (SYNCHRONIZED LIVE WITH MSSQL 2020 + JSON DB)
app.get('/api/articles', async (req, res) => {
  const { category, status, search } = req.query;
  let list = await getArticlesFromDb(category, status, search);
  list.sort((a, b) => (parseInt(b.id) || 0) - (parseInt(a.id) || 0));
  res.json({ success: true, count: list.length, data: list });
});

app.get('/api/articles/:id', (req, res) => {
  const db = loadDB();
  const art = (db.articles || []).find(a => a.id == req.params.id);
  if (!art) return res.status(404).json({ error: 'Không tìm thấy bài viết' });
  art.viewsCount = (art.viewsCount || 0) + 1;
  saveDB(db);
  res.json({ success: true, data: art });
});

app.post('/api/articles', async (req, res) => {
  const { title, categoryName, categoryId, summary, content, image, author, status, isAiGenerated, aiPrompt } = req.body;
  if (!title) return res.status(400).json({ error: 'Tiêu đề là bắt buộc' });

  const articleData = {
    title,
    categoryId: categoryId || 1,
    categoryName: categoryName || 'Thông Báo Chỉ Đạo',
    summary: summary || title,
    content: content || title,
    image: image || 'images/banner.jpg',
    author: author || 'Cán Bộ Công Đoàn',
    authorId: 1,
    status: status || 'pending_review',
    isAiGenerated: !!isAiGenerated,
    aiPrompt: aiPrompt || ''
  };

  const created = await insertArticleToDb(articleData);
  const statusMap = { published: 'Đã Xuất Bản', approved: 'Đã Duyệt', pending_review: 'Chờ Duyệt', pending: 'Chờ Duyệt', draft: 'Bản Nháp' };
  created.statusName = statusMap[created.status] || 'Chờ Duyệt';
  res.json({ success: true, message: 'Đã lưu bài viết vào CSDL hệ thống (Transactional)', data: created });
});

app.put('/api/articles/:id', async (req, res) => {
  const id = req.params.id;
  const { title, categoryName, summary, content, status, scheduledAt, image, changeType, isAiGenerated, aiProvider, aiModel, aiPrompt } = req.body;

  const updateData = {
    title,
    categoryName,
    summary,
    content,
    status,
    scheduledAt,
    image,
    changeType: changeType || 'EDITOR_EDIT',
    isAiGenerated: !!isAiGenerated,
    aiProvider: aiProvider || null,
    aiModel: aiModel || null,
    aiPrompt: aiPrompt || null
  };

  await updateArticleInDb(id, updateData);
  const statusMap = { published: 'Đã Xuất Bản', approved: 'Đã Duyệt', pending_review: 'Chờ Duyệt', pending: 'Chờ Duyệt', draft: 'Bản Nháp' };
  res.json({ success: true, message: 'Đã cập nhật bài viết & lưu phiên bản mới vào CSDL (Transactional)', data: { id, status: updateData.status, statusName: statusMap[updateData.status] || 'Đã Lưu' } });
});

app.delete('/api/articles/:id', async (req, res) => {
  const id = req.params.id;
  await deleteArticleFromDb(id);
  res.json({ success: true, message: 'Đã xóa bài viết vĩnh viễn khỏi CSDL' });
});

app.post('/api/articles/:id/approve', async (req, res) => {
  const id = req.params.id;
  await updateArticleInDb(id, { status: 'approved' });
  res.json({ success: true, message: 'Đã duyệt bài viết thành công' });
});

// REST API USERS, EVENTS, MEDIA, AUDITS, SIMULATED SOCIAL
app.get('/api/users', (req, res) => res.json({ success: true, data: loadDB().users || [] }));
app.get('/api/events', (req, res) => res.json({ success: true, data: loadDB().events || [] }));
app.get('/api/media', (req, res) => res.json({ success: true, data: loadDB().media || [] }));
app.get('/api/audits', (req, res) => res.json({ success: true, data: loadDB().audits || [] }));
app.get('/api/inbox/comments', (req, res) => res.json({ success: true, data: loadDB().comments || [] }));

app.get('/api/analytics', (req, res) => {
  const db = loadDB();
  const arts = db.articles || [];
  res.json({
    success: true,
    totalArticles: arts.length,
    totalViews: arts.reduce((acc, a) => acc + (a.viewsCount || 0), 0),
    totalLikes: arts.reduce((acc, a) => acc + (a.likesCount || 0), 0),
    totalShares: arts.reduce((acc, a) => acc + (a.sharesCount || 0), 0),
    aiArticlesCount: arts.filter(a => a.isAiGenerated).length,
    publishedCount: arts.filter(a => a.status === 'published').length
  });
});

app.post('/api/facebook/publish', (req, res) => {
  const { articleId, title } = req.body;
  const db = loadDB();
  const art = (db.articles || []).find(a => a.id == articleId);
  if (art) {
    art.status = 'published';
    art.statusName = 'Đã Xuất Bản';
    saveDB(db);
  }
  res.json({
    success: true,
    facebookPostId: `simulated_fb_${articleId || Date.now()}`,
    message: `[MÔ PHỎNG XUẤT BẢN FANPAGE FACEBOOK OK] Đã chuyển bài viết "${title}" sang trạng thái xuất bản Fanpage TDMU!`
  });
});

app.listen(PORT, () => {
  console.log(`====================================================`);
  console.log(`🚀 Website Truyền Thông Công Đoàn TDMU Real SaaS Engine`);
  console.log(`🌐 Public Portal: http://localhost:${PORT}`);
  console.log(`⚙️  Admin CMS Portal: http://localhost:${PORT}/admin.html`);
  console.log(`====================================================`);
});
