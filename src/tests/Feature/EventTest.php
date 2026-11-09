<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    // フォームから送る正しい値。テストごとに一部だけ変えて使う
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'title'       => 'テストイベント',
            'description' => 'イベントの内容です',
            'location'    => '東京',
            'starts_at'   => now()->addWeek()->format('Y-m-d H:i:s'),
            'capacity'    => 10,
        ], $overrides);
    }

    // TC-E1：一覧が開ける
    public function test_event_list_can_be_viewed(): void
    {
        $event = Event::factory()->create();

        $this->get('/events')->assertOk()->assertSee($event->title);
    }

    // TC-E2：作成画面が開ける
    public function test_event_create_page_can_be_viewed(): void
    {
        $this->get('/events/create')->assertOk();
    }

    // TC-E3：正しい値で作成できて、詳細画面に移動する
    public function test_event_can_be_created(): void
    {
        $response = $this->post('/events', $this->validData());

        $event = Event::where('title', 'テストイベント')->first();
        $response->assertRedirect(route('events.show', $event));
        $this->get(route('events.show', $event))->assertOk()->assertSee('テストイベント');
    }

    // TC-E4（境界値）：定員が0だとエラーになる
    public function test_capacity_of_zero_is_rejected(): void
    {
        $response = $this->post('/events', $this->validData(['capacity' => 0]));

        $response->assertSessionHasErrors(['capacity']);
        $this->assertDatabaseCount('events', 0);
    }

    // TC-E5（異常系）：過去の日時だとエラーになる
    public function test_past_start_date_is_rejected(): void
    {
        $response = $this->post('/events', $this->validData([
            'starts_at' => now()->subDay()->format('Y-m-d H:i:s'),
        ]));

        $response->assertSessionHasErrors(['starts_at']);
        $this->assertDatabaseCount('events', 0);
    }
}
