<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PostController;
use Illuminate\Support\Facades\Route;

// routes/api.php のルートには自動で /api プレフィックスが付く
// GET  /api/posts      → 投稿一覧
// POST /api/posts      → 投稿作成
// GET  /api/posts/{id} → 投稿詳細
// GET  /api/categories → カテゴリー一覧（フォームの選択肢用）
Route::get('/posts', [PostController::class, 'index']);
Route::post('/posts', [PostController::class, 'store']);
Route::get('/posts/{id}', [PostController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
