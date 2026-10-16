<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;

class PostController extends Controller
{
    public function __construct(
        private PostService $postService,
    ) {}

    public function index()
    {
        // with('user') で投稿者をまとめて取得（N+1 対策）
        $posts = Post::with('user')->latest()->get();

        // collection() で { "data": [ ... ] } の形に包んで返す
        return PostResource::collection($posts);
    }

    public function store(PostRequest $request)
    {
        // バリデーションに失敗した場合は、ここに来る前に 422 + エラー内容の JSON が自動で返る
        // API には認証がないため、投稿者なし（null）で作成する
        $post = $this->postService->createPost(null, $request->validated());

        // 新規作成したモデルを Resource で返すと、ステータスは自動で 201 Created になる
        return new PostResource($post->load('user'));
    }

    public function show($id)
    {
        $post = Post::with('user')->findOrFail($id);

        return new PostResource($post);
    }
}
