<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostRequest;
use App\Http\Resources\PostResource;
use App\Repositories\PostRepository;
use App\Services\PostService;

class PostController extends Controller
{
    // 読むだけの処理は Repository、作成などの処理は Service を使う（Web 側の PostController と同じ分け方）
    public function __construct(
        private PostService $postService,
        private PostRepository $postRepository,
    ) {}

    public function index()
    {
        // Repository 側で with('user') を使い、投稿者をまとめて取得している（N+1 対策）
        $posts = $this->postRepository->getLatestWithUser();

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

    public function show(int $id)
    {
        $post = $this->postRepository->findWithUser($id);

        return new PostResource($post);
    }
}
