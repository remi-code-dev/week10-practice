<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    // TC-A1：投稿一覧のJSONが返る
    public function test_api_returns_post_list(): void
    {
        // ① 準備
        Post::factory()->count(2)->create();

        // ② 実行
        $response = $this->getJson('/api/posts');

        // ③ 確認
        $response->assertOk()->assertJsonCount(2, 'data');
    }

    // TC-A2：詳細のJSONが返る
    public function test_api_returns_post_detail(): void
    {
        $post = Post::factory()->create(['title' => 'APIの投稿']);

        $response = $this->getJson("/api/posts/{$post->id}");

        $response->assertOk()->assertJsonPath('data.title', 'APIの投稿');
    }

    // TC-A3：作成できて、201 が返る
    public function test_api_can_create_post(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/posts', [
            'title'       => 'APIから投稿',
            'content'     => '本文です',
            'category_id' => $category->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('posts', ['title' => 'APIから投稿']);
    }

    // TC-A4：タイトルなしだと、422 が返る
    public function test_api_rejects_post_without_title(): void
    {
        $category = Category::factory()->create();

        $response = $this->postJson('/api/posts', [
            'content'     => '本文です',
            'category_id' => $category->id,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('posts', 0);
    }

    // TC-A5：カテゴリー一覧が返る
    public function test_api_returns_categories(): void
    {
        Category::factory()->count(2)->create();

        $response = $this->getJson('/api/categories');

        $response->assertOk()->assertJsonCount(2, 'data');
    }
}
