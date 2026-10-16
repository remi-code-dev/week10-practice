<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Repositories\PostRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Service: 投稿に関する処理の流れ（トランザクション・ログなど）を担当する
class PostService
{
    // コンストラクタインジェクション（DIパターン、次のSection 4で詳しく説明）
    // 「private PostRepository $postRepository」はPHP 8のコンストラクタプロモーションという書き方
    // 従来の書き方と同じ意味：
    //   private PostRepository $postRepository;
    //   public function __construct(PostRepository $postRepository) {
    //       $this->postRepository = $postRepository;
    //   }
    public function __construct(
        private PostRepository $postRepository
    ) {}

    // $user が null の場合は投稿者なしで作成する（認証のない API から呼ばれる場合）
    public function createPost(?User $user, array $data)
    {
        // DB::transaction(): この中の処理が全て成功すれば確定、途中でエラーが起きたら全て取り消す
        return DB::transaction(function () use ($user, $data) {
            $post = $user
                ? $this->postRepository->createForUser($user, $data)
                : $this->postRepository->create($data);

            Log::info('Post created', ['post_id' => $post->id]);

            return $post;
        });
    }

    public function updatePost(Post $post, array $data)
    {
        return DB::transaction(function () use ($post, $data) {
            $updated = $this->postRepository->update($post, $data);

            Log::info('Post updated', ['post_id' => $updated->id]);

            return $updated;
        });
    }

    public function deletePost(Post $post)
    {
        $this->postRepository->delete($post);

        Log::info('Post deleted', ['post_id' => $post->id]);
    }
}
