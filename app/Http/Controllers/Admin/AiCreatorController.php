<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GeminiAiService;
use Illuminate\Http\Request;

class AiCreatorController extends Controller {
    protected GeminiAiService $aiService;

    public function __construct(GeminiAiService $aiService) {
        $this->aiService = $aiService;
    }

    public function index() {
        return view('admin.ai.index');
    }

    public function generate(Request $request) {
        $request->validate([
            'prompt' => 'required|string',
            'category' => 'required|string',
            'tone' => 'required|string'
        ]);

        $customKey = $request->input('api_key');
        if ($customKey) {
            $service = new GeminiAiService($customKey);
            $result = $service->generateArticleContent($request->prompt, $request->category, $request->tone);
        } else {
            $result = $this->aiService->generateArticleContent($request->prompt, $request->category, $request->tone);
        }

        return response()->json($result);
    }
}
