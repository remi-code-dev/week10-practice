<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory;

    protected $fillable = ['title', 'description', 'location', 'starts_at', 'capacity'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime'];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * 残席数（キャンセル済みの予約は含めない）
     */
    public function remainingSeats(): int
    {
        // まとめて取った合計（withSum）があればそれを使い、なければ今まで通りSQLで数える
        $reserved = array_key_exists('reservations_sum_guests', $this->attributes)
            ? (int) $this->reservations_sum_guests
            : (int) $this->reservations()->whereNull('cancelled_at')->sum('guests');

        return max(0, $this->capacity - $reserved);
    }
}
