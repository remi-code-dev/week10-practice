<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Support;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class SupportController extends Controller
{
    private const AMOUNT = 500;  // 応援の金額（円）。今週は定額にする

    public function create(Post $post)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::create([
            'line_items' => [[
                'price_data' => [
                    'currency'     => 'jpy',
                    'product_data' => ['name' => '「' . $post->title . '」の書き手を応援'],
                    'unit_amount'  => self::AMOUNT,
                ],
                'quantity' => 1,
            ]],
            'mode'        => 'payment',
            // 決済が終わったあとで「どの記事を・誰が応援したか」が分かるように、Stripe に一緒に預けておく
            'metadata'    => ['post_id' => $post->id, 'user_id' => auth()->id()],
            'success_url' => route('supports.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('posts.show', $post),
        ]);

        return redirect($session->url);
    }

    public function success(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        // URL の session_id から、Stripe に決済の結果を問い合わせる
        $session = Session::retrieve($request->query('session_id'));

        if ($session->payment_status !== 'paid') {
            return redirect()->route('posts.index')->with('error', '決済が完了していません');
        }

        // 応援した履歴を保存する（同じ session_id なら二重に保存しない）
        Support::firstOrCreate(
            ['stripe_session_id' => $session->id],
            [
                'user_id' => $session->metadata->user_id,
                'post_id' => $session->metadata->post_id,
                'amount'  => $session->amount_total,  // 円の場合は、この値がそのまま金額（円）
            ]
        );

        return view('supports.success');
    }
}
