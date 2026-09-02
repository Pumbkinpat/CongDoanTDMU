<?php

namespace App\Http\Controllers;

use App\Models\ArticleAudit;

class AuditController extends Controller
{
    public function index()
    {
        $audits = ArticleAudit::with('user', 'article')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'articleId' => $a->article_id,
                    'userName' => $a->user_name,
                    'action' => $a->action,
                    'details' => $a->details,
                    'createdAt' => $a->created_at
                ];
            });

        return response()->json(['success' => true, 'data' => $audits]);
    }
}