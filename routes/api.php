<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AiStudioController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\FacebookController;

// Articles CRUD
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{id}', [ArticleController::class, 'show']);
Route::post('/articles', [ArticleController::class, 'store']);
Route::put('/articles/{id}', [ArticleController::class, 'update']);
Route::delete('/articles/{id}', [ArticleController::class, 'destroy']);

// Article Actions
Route::post('/articles/{id}/approve', [ArticleController::class, 'approve']);
Route::post('/articles/{id}/like', [ArticleController::class, 'like']);
Route::post('/articles/{id}/comments', [ArticleController::class, 'addComment']);

// Comments
Route::delete('/comments/{id}', [CommentController::class, 'destroy']);

// Users
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);

// Events
Route::get('/events', [EventController::class, 'index']);
Route::post('/events', [EventController::class, 'store']);
Route::delete('/events/{id}', [EventController::class, 'destroy']);

// Media
Route::get('/media', [MediaController::class, 'index']);
Route::post('/media/upload', [MediaController::class, 'upload']);
Route::delete('/media/{id}', [MediaController::class, 'destroy']);

// Analytics & Audit
Route::get('/analytics', [AnalyticsController::class, 'index']);
Route::get('/audits', [AuditController::class, 'index']);

// Inbox
Route::get('/inbox/comments', [InboxController::class, 'index']);

// AI Content Generator
Route::post('/ai/generate', [AiStudioController::class, 'generate']);
Route::post('/ai/quality-check', [AiStudioController::class, 'qualityCheck']);
Route::post('/ai/floating-command', [AiStudioController::class, 'floatingCommand']);
Route::post('/ai/repurpose', [AiStudioController::class, 'repurpose']);
Route::post('/ai/event-plan-generator', [AiStudioController::class, 'eventPlanGenerator']);
Route::post('/ai/image-prompt-generator', [AiStudioController::class, 'imagePromptGenerator']);
Route::post('/ai/chat', [AiStudioController::class, 'chat']);

// Facebook
Route::post('/facebook/publish', [FacebookController::class, 'publish']);
