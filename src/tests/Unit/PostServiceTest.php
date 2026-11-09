<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostServiceTest extends TestCase
{
    use RefreshDatabase;

    private PostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PostService::class);
    }

    // TC-01：ユーザーを渡すと、そのユーザーの投稿としてDBに保存される
    public function test_create_post_saves_post_for_user(): void
    {
        // ① 準備
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // ② 実行
        $this->service->createPost($user, [
            'title'       => 'テスト投稿',
            'content'     => '本文です',
            'category_id' => $category->id,
        ]);

        // ③ 確認
        $this->assertDatabaseHas('posts', [
            'title'   => 'テスト投稿',
            'user_id' => $user->id,
        ]);
    }

    // TC-02：ユーザーが null なら、投稿者なしで保存される
    public function test_create_post_without_user(): void
    {
        // ① 準備
        $category = Category::factory()->create();

        // ② 実行
        $this->service->createPost(null, [
            'title'       => '投稿者なしの投稿',
            'content'     => '本文です',
            'category_id' => $category->id,
        ]);

        // ③ 確認
        $this->assertDatabaseHas('posts', [
            'title'   => '投稿者なしの投稿',
            'user_id' => null,
        ]);
    }

    // TC-03：画像を渡すと、S3に保存され、image_path がDBに入る
    public function test_create_post_with_image_saves_image_path(): void
    {
        // ① 準備
        Storage::fake('s3');
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $image = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg');

        // ② 実行
        $post = $this->service->createPost($user, [
            'title'       => '画像付きの投稿',
            'content'     => '本文です',
            'category_id' => $category->id,
        ], $image);

        // ③ 確認
        Storage::disk('s3')->assertExists($post->image_path);
    }

    // TC-04：title がないと、DBのエラーになる
    public function test_create_post_without_title_throws_exception(): void
    {
        // ① 準備
        $category = Category::factory()->create();

        // ③ 確認の予告
        $this->expectException(QueryException::class);

        // ② 実行
        $this->service->createPost(null, [
            'content'     => '本文です',
            'category_id' => $category->id,
        ]);
    }

    // TC-05：タイトルを変えると、DBも更新される
    public function test_update_post_changes_title(): void
    {
        // ① 準備
        $post = Post::factory()->create(['title' => '古いタイトル']);

        // ② 実行
        $this->service->updatePost($post, ['title' => '新しいタイトル']);

        // ③ 確認
        $this->assertDatabaseHas('posts', [
            'id'    => $post->id,
            'title' => '新しいタイトル',
        ]);
    }

    // TC-06：画像を渡さなければ、今の画像のまま
    public function test_update_post_keeps_image_when_no_new_image(): void
    {
        // ① 準備
        $post = Post::factory()->create(['image_path' => 'images/old.jpg']);

        // ② 実行
        $this->service->updatePost($post, ['title' => '新しいタイトル']);

        // ③ 確認
        $this->assertDatabaseHas('posts', [
            'id'         => $post->id,
            'image_path' => 'images/old.jpg',
        ]);
    }

    // TC-07：新しい画像を渡すと、image_path が変わる
    public function test_update_post_replaces_image_path(): void
    {
        // ① 準備
        Storage::fake('s3');
        $post = Post::factory()->create(['image_path' => 'images/old.jpg']);
        $image = UploadedFile::fake()->create('new.jpg', 100, 'image/jpeg');

        // ② 実行
        $updated = $this->service->updatePost($post, ['title' => '新しいタイトル'], $image);

        // ③ 確認
        $this->assertNotEquals('images/old.jpg', $updated->image_path);
    }

    // TC-08：投稿がDBから消える
    public function test_delete_post_removes_post(): void
    {
        // ① 準備
        $post = Post::factory()->create();

        // ② 実行
        $this->service->deletePost($post);

        // ③ 確認
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    // TC-09（境界値）：タイトル255文字は保存できる
    public function test_create_post_with_title_of_255_characters(): void
    {
        // ① 準備
        $category = Category::factory()->create();
        $title = str_repeat('a', 255);

        // ② 実行
        $this->service->createPost(null, [
            'title'       => $title,
            'content'     => '本文です',
            'category_id' => $category->id,
        ]);

        // ③ 確認
        $this->assertDatabaseHas('posts', ['title' => $title]);
    }
}
