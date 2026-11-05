<?php

namespace App\Http\Controllers;

use App\Models\Support;
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
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            // 支払いが済んでいるときだけ記録する
            if ($session->payment_status === 'paid') {
                // ブラウザを閉じて完了ページが開かれなくても、ここで応援した履歴が残る
                Support::recordFromCheckoutSession($session);
                Log::info('Checkout完了: ' . $session->id); // cs_ から始まる支払いページのID
            }
        } elseif ($event->type === 'payment_intent.succeeded') {
            // 練習課題2：お金のやりとりが成功したら、決済IDをログに記録する
            $paymentIntent = $event->data->object;
            Log::info('決済完了: ' . $paymentIntent->id); // pi_ から始まる決済ID
        }

        return response('OK', 200);
    }
}
