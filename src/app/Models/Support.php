<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Support extends Model
{
    protected $fillable = ['user_id', 'post_id', 'amount', 'stripe_session_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    // Stripe の Checkout Session から、応援した履歴を保存する
    // 完了ページと Webhook の両方から呼ばれるので、同じ session_id なら二重に保存しない
    public static function recordFromCheckoutSession($session): self
    {
        return self::createOrFirst(
            ['stripe_session_id' => $session->id],
            [
                'user_id' => $session->metadata->user_id,
                'post_id' => $session->metadata->post_id,
                'amount'  => $session->amount_total,  // 円の場合は、この値がそのまま金額（円）
            ]
        );
    }
}
