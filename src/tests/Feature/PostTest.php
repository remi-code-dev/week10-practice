<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/posts')->assertRedirect('/login');
    }

    public function test_user_can_view_posts(): void
    {
        $post = Post::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get('/posts')
            ->assertOk()
            ->assertSee($post->title);
    }

    // 詳細画面：投稿のタイトルと本文が表示される
    public function test_user_can_view_post_detail(): void
    {
        $post = Post::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get("/posts/{$post->id}")
            ->assertOk()
            ->assertSee($post->title)
            ->assertSee($post->content);
    }

    // TC-10（境界値）：タイトル256文字はバリデーションエラーになる
    public function test_title_of_256_characters_is_rejected(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs(User::factory()->create())->post('/posts', [
            'title'       => str_repeat('a', 256),
            'content'     => '本文',
            'category_id' => $category->id,
        ]);

        $response->assertSessionHasErrors(['title']);
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_created_post_belongs_to_logged_in_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($user)->post('/posts', [
            'title' => 'タイトル',
            'content' => '本文',
            'category_id' => $category->id,
            'user_id' => $other->id,
        ]);

        $this->assertDatabaseHas('posts', ['title' => 'タイトル', 'user_id' => $user->id]);
    }

    public function test_validation_messages_are_japanese(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/posts', [])
            ->assertSessionHasErrors([
                'title' => 'タイトルは必須です。',
                'content' => '内容は必須です。',
                'category_id' => 'カテゴリーは必須です。',
            ]);
    }

    public function test_owner_can_edit_and_update_post(): void
    {
        $post = Post::factory()->create();

        $this->actingAs($post->user)->get("/posts/{$post->id}/edit")->assertOk();

        $this->actingAs($post->user)
            ->put("/posts/{$post->id}", [
                'title' => '更新後',
                'content' => '更新後の本文',
                'category_id' => $post->category_id,
            ])
            ->assertRedirect("/posts/{$post->id}");

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => '更新後']);
    }

    public function test_other_user_cannot_edit_update_or_delete_post(): void
    {
        $post = Post::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($other)->get("/posts/{$post->id}/edit")->assertForbidden();
        $this->actingAs($other)
            ->put("/posts/{$post->id}", [
                'title' => '乗っ取り',
                'content' => '本文',
                'category_id' => $post->category_id,
            ])
            ->assertForbidden();
        $this->actingAs($other)->delete("/posts/{$post->id}")->assertForbidden();

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => $post->title]);
    }

    public function test_owner_can_delete_post(): void
    {
        $post = Post::factory()->create();

        $this->actingAs($post->user)
            ->delete("/posts/{$post->id}")
            ->assertRedirect('/posts');

        $this->assertModelMissing($post);
    }

    public function test_edit_buttons_shown_only_to_owner(): void
    {
        $post = Post::factory()->create();
        $editUrl = route('posts.edit', $post);

        $this->actingAs($post->user)->get("/posts/{$post->id}")->assertSee($editUrl, false);
        $this->actingAs(User::factory()->create())->get("/posts/{$post->id}")->assertDontSee($editUrl, false);
    }
}
