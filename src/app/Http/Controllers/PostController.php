<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Models\Post;
use App\Repositories\CategoryRepository;
use App\Repositories\PostRepository;
use App\Services\PostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

// コントローラーは「受け取って渡す」だけ。処理の詳細はService/Repositoryに任せる
class PostController extends Controller
{
    public function __construct(
        private PostService $postService,
        private PostRepository $postRepository,
        private CategoryRepository $categoryRepository
    ) {}

    /**
     * 投稿一覧表示（ページネーション付き）
     */
    public function index(): View
    {
        $posts = $this->postRepository->getAll();

        return view('posts.index', compact('posts'));
    }

    /**
     * 投稿作成フォームの表示
     */
    public function create(): View
    {
        $categories = $this->categoryRepository->getAllOrderByName();

        return view('posts.create', compact('categories'));
    }

    /**
     * 投稿の保存
     */
    public function store(PostRequest $request): RedirectResponse
    {
        // $request->file('image'): フォームで選ばれた画像ファイル（選ばれていなければ null）
        $post = $this->postService->createPost($request->user(), $request->validated(), $request->file('image'));

        return redirect()
            ->route('posts.show', $post)
            ->with('status', '投稿を作成しました。');
    }

    /**
     * 投稿詳細表示
     */
    public function show(Post $post): View
    {
        $post->load(['category', 'user']);

        return view('posts.show', compact('post'));
    }

    /**
     * 投稿編集フォームの表示
     */
    public function edit(Post $post): View
    {
        // 権限がなければ403（Forbidden）レスポンスを自動で返す（PostPolicy::update）
        $this->authorize('update', $post);

        $categories = $this->categoryRepository->getAllOrderByName();

        return view('posts.edit', compact('post', 'categories'));
    }

    /**
     * 投稿の更新
     */
    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $post = $this->postService->updatePost($post, $request->validated(), $request->file('image'));

        return redirect()
            ->route('posts.show', $post)
            ->with('status', '投稿を更新しました。');
    }

    /**
     * 投稿の削除
     */
    public function destroy(Post $post): RedirectResponse
    {
        // PostPolicy::delete
        $this->authorize('delete', $post);

        $this->postService->deletePost($post);

        return redirect()
            ->route('posts.index')
            ->with('status', '投稿を削除しました。');
    }
}
