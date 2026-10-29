<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Support;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\InvalidRequestException;

class SupportController extends Controller
{
    private const AMOUNT = 500;  // 応援の金額（円）。今週は定額にする

    public function create(Post $post)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        // Stripe との通信は失敗することがあるので、try-catch で受け止める
        try {
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

        } catch (InvalidRequestException $e) {
            // パラメータが不正（コード側のバグの可能性が高い）
            Log::error('Stripe InvalidRequest: ' . $e->getMessage());
            return back()->withErrors(['payment' => '決済リクエストが不正です']);

        } catch (ApiConnectionException $e) {
            // Stripe に接続できなかった（一時的なネットワーク障害）
            Log::error('Stripe ApiConnection: ' . $e->getMessage());
            return back()->withErrors(['payment' => '通信エラーが発生しました。時間をおいて再試行してください']);

        } catch (AuthenticationException $e) {
            // API キーが不正（.env の STRIPE_SECRET を確認する）。キーの話はユーザーには見せない
            Log::error('Stripe Authentication: ' . $e->getMessage());
            return back()->withErrors(['payment' => '現在、応援機能をご利用いただけません']);

        } catch (ApiErrorException $e) {
            // 上で拾えなかったその他の Stripe エラー。親クラスなので必ず最後に書く
            Log::error('Stripe Error: ' . $e->getMessage());
            return back()->withErrors(['payment' => '決済の準備中にエラーが発生しました']);
        }
    }

    public function success(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $sessionId = $request->query('session_id');

        // URL の session_id から、Stripe に決済の結果を問い合わせる
        try {
            $session = Session::retrieve($sessionId);

        } catch (InvalidRequestException $e) {
            // session_id が存在しない（URL を書き換えられた等）
            Log::warning('Stripe InvalidRequest (success): ' . $e->getMessage(), ['session_id' => $sessionId]);
            return redirect()->route('posts.index')->with('error', '決済情報が見つかりませんでした');

        } catch (ApiErrorException $e) {
            // 通信エラー等。ここに来た時点で支払いは済んでいる可能性があるので、あとで調べられるよう session_id も残す
            Log::error('Stripe Error (success): ' . $e->getMessage(), ['session_id' => $sessionId]);
            return redirect()->route('posts.index')
                ->with('error', '決済結果の確認中にエラーが発生しました。お支払いが完了している場合もあるため、お手数ですがお問い合わせください');
        }

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
