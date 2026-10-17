<?php

namespace App\Repositories;

use App\Models\Post;
use App\Models\User;

// Repository: データの取得・保存（Eloquentの操作）だけを担当する
class PostRepository
{
    // 全件取得（カテゴリー付き・最新順・ページネーション付き）
    public function getAll()
    {
        return Post::with('category')
            ->latest()
            ->paginate(10);
    }

    // 全件取得（投稿者付き・最新順）。API の一覧用
    public function getLatestWithUser()
    {
        return Post::with('user')
            ->latest()
            ->get();
    }

    // IDで1件取得
    public function findById(int $id)
    {
        return Post::findOrFail($id);
    }

    // IDで1件取得（投稿者付き）。API の詳細用
    public function findWithUser(int $id)
    {
        return Post::with('user')->findOrFail($id);
    }

    // 特定ユーザーの投稿を取得
    public function getByUser(int $userId)
    {
        return Post::where('user_id', $userId)
            ->latest()
            ->get();
    }

    // 投稿者はリクエストではなく、渡されたユーザーに紐づけて作成する
    public function createForUser(User $user, array $data)
    {
        return $user->posts()->create($data);
    }

    // 投稿者なしで作成する（認証のない API からの投稿用）
    public function create(array $data)
    {
        return Post::create($data);
    }

    public function update(Post $post, array $data)
    {
        $post->update($data);
        return $post;
    }

    public function delete(Post $post)
    {
        return $post->delete();
    }
}
