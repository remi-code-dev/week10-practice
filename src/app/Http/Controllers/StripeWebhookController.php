<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            // 署名が不正 → 偽のリクエストを拒否
            return response('Invalid signature', 400);
        }

        // イベントの種類に応じて処理を分岐
        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;
            Log::info('決済完了: ' . $paymentIntent->id);
            // 例: 応援した履歴（supports）の記録や、書き手へのお知らせメールの送信など
        }

        return response('OK', 200);
    }
}
