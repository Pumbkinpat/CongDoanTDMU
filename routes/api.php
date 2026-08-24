<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AiStudioController;

Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{id}', [ArticleController::class, 'show']);
Route::post('/articles', [ArticleController::class, 'store']);
Route::put('/articles/{id}', [ArticleController::class, 'update']);
Route::delete('/articles/{id}', [ArticleController::class, 'destroy']);
Route::post('/articles/{id}/approve', [ArticleController::class, 'approve']);

Route::post('/ai/generate', [AiStudioController::class, 'generate']);
Route::post('/ai/quality-check', [AiStudioController::class, 'qualityCheck']);
Route::post('/ai/floating-command', [AiStudioController::class, 'floatingCommand']);
Route::post('/ai/repurpose', [AiStudioController::class, 'repurpose']);
