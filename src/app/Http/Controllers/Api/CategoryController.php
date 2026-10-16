<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Repositories\CategoryRepository;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryRepository $categoryRepository,
    ) {}

    // 投稿フォームのセレクトボックス用
    public function index()
    {
        return CategoryResource::collection($this->categoryRepository->getAllOrderByName());
    }
}
