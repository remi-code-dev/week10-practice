<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Repositories\PostRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
    // $image は任意。デフォルトが null なので、画像を渡さない API 側の呼び出しはそのまま動く
    public function createPost(?User $user, array $data, ?UploadedFile $image = null)
    {
        $data = $this->uploadImage($data, $image);

        // DB::transaction(): この中の処理が全て成功すれば確定、途中でエラーが起きたら全て取り消す
        return DB::transaction(function () use ($user, $data) {
            $post = $user
                ? $this->postRepository->createForUser($user, $data)
                : $this->postRepository->create($data);

            Log::info('Post created', ['post_id' => $post->id]);

            return $post;
        });
    }

    // 新しい画像が選ばれたときだけ image_path を上書きする（選ばれなければ今の画像のまま）
    public function updatePost(Post $post, array $data, ?UploadedFile $image = null)
    {
        $data = $this->uploadImage($data, $image);

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

    // 画像があればS3にアップロードし、返ってきた保存先パスを $data['image_path'] に入れる
    // S3はDBではないのでトランザクションでは取り消せない。そのためDB処理の前（トランザクションの外）で行う
    private function uploadImage(array $data, ?UploadedFile $image): array
    {
        // 'image' はファイルそのもの（UploadedFile）なので、DBに保存する $data からは外す
        unset($data['image']);

        if ($image) {
            // put('images', ファイル): S3の images/ フォルダに、重複しないランダムなファイル名で保存する
            // 戻り値は保存先のパス（例: images/aBcD1234.jpg）。URLではなくパスをDBに保存する
            $data['image_path'] = Storage::disk('s3')->put('images', $image);
        }

        return $data;
    }
}
