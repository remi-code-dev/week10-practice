<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    // フォームから送る正しい値。テストごとに一部だけ変えて使う
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name'        => '山田太郎',
            'email'       => 'taro@example.com',
            'guests'      => 2,
            'reserved_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ], $overrides);
    }

    // TC-R1：一覧・予約画面が開ける
    public function test_reservation_pages_can_be_viewed(): void
    {
        // ① 準備
        $reservation = Reservation::factory()->create();

        // ② 実行・③ 確認
        $this->get('/reservations')->assertOk()->assertSee($reservation->name);
        $this->get("/events/{$reservation->event_id}/reservations/create")->assertOk();
    }

    // TC-R2：正しい値で予約できる
    public function test_reservation_can_be_created(): void
    {
        // ① 準備
        $event = Event::factory()->create(['capacity' => 10]);

        // ② 実行
        $response = $this->post("/events/{$event->id}/reservations", $this->validData());

        // ③ 確認
        $response->assertRedirect(route('reservations.index'));
        $this->assertDatabaseHas('reservations', [
            'event_id' => $event->id,
            'email'    => 'taro@example.com',
        ]);
    }

    // TC-R3（境界値）：残席ちょうどの人数は予約できる
    public function test_guests_equal_to_remaining_seats_is_accepted(): void
    {
        // ① 準備：定員5人のイベント
        $event = Event::factory()->create(['capacity' => 5]);

        // ② 実行：5人で予約する
        $response = $this->post("/events/{$event->id}/reservations", $this->validData(['guests' => 5]));

        // ③ 確認
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('reservations', 1);
    }

    // TC-R4（境界値）：残席より1人多いとエラーになる
    public function test_guests_over_remaining_seats_is_rejected(): void
    {
        // ① 準備：定員5人のイベント
        $event = Event::factory()->create(['capacity' => 5]);

        // ② 実行：6人で予約する
        $response = $this->post("/events/{$event->id}/reservations", $this->validData(['guests' => 6]));

        // ③ 確認
        $response->assertSessionHasErrors(['guests']);
        $this->assertDatabaseCount('reservations', 0);
    }

    // TC-R5：キャンセルすると cancelled_at が入る
    public function test_reservation_can_be_cancelled(): void
    {
        $reservation = Reservation::factory()->create();

        $this->patch("/reservations/{$reservation->id}/cancel");

        $this->assertNotNull($reservation->fresh()->cancelled_at);
    }

    // TC-R6：キャンセルすると残席が戻る
    public function test_cancelling_restores_remaining_seats(): void
    {
        $event = Event::factory()->create(['capacity' => 5]);
        $reservation = Reservation::factory()->create(['event_id' => $event->id, 'guests' => 3]);

        $this->patch("/reservations/{$reservation->id}/cancel");

        $this->assertEquals(5, $event->remainingSeats());
    }
}
