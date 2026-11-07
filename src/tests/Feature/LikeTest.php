<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikeTest extends TestCase
{
    use RefreshDatabase;

    // TC-L1：ログイン中に押すと、likes に1行増えて、詳細画面に戻る
    public function test_user_can_like_post(): void
    {
        // ① 準備
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // ② 実行
        $response = $this->actingAs($user)->post("/posts/{$post->id}/like");

        // ③ 確認
        $response->assertRedirect("/posts/{$post->id}");
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }

    // TC-L2：同じ人がもう一度押すと、いいねが取り消される
    public function test_user_can_unlike_post(): void
    {
        // ① 準備：すでにいいねしている状態を作る
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $post->likedUsers()->attach($user->id);

        // ② 実行：もう一度押す
        $this->actingAs($user)->post("/posts/{$post->id}/like");

        // ③ 確認：いいねが消えている
        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }

    // TC-L3：別の人も押すと、いいねが2件になる
    public function test_multiple_users_can_like_same_post(): void
    {
        $post = Post::factory()->create();
        $post->likedUsers()->attach(User::factory()->create()->id);

        $this->actingAs(User::factory()->create())->post("/posts/{$post->id}/like");

        $this->assertDatabaseCount('likes', 2);
    }

    // TC-L4：ログインしていないと、/login に移動して、いいねは増えない
    public function test_guest_cannot_like_post(): void
    {
        $post = Post::factory()->create();

        $response = $this->post("/posts/{$post->id}/like");

        $response->assertRedirect('/login');
        $this->assertDatabaseCount('likes', 0);
    }

    // TC-L5：存在しない投稿に押すと、404 になる
    public function test_like_returns_404_for_missing_post(): void
    {
        $response = $this->actingAs(User::factory()->create())->post('/posts/999/like');

        $response->assertNotFound();
    }

    // TC-L6：詳細画面に、いいね数とボタンが表示される
    public function test_post_detail_shows_like_count_and_button(): void
    {
        // ① 準備：2人がいいねした投稿を作る
        $post = Post::factory()->create();
        $post->likedUsers()->attach(User::factory()->count(2)->create()->pluck('id'));

        // ② 実行：詳細画面を開く
        $response = $this->actingAs(User::factory()->create())->get("/posts/{$post->id}");

        // ③ 確認
        $response->assertSee('いいね 2');
        $response->assertSee(route('likes.toggle', $post), false);
    }
}
