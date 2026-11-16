<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EventController extends Controller
{
    /**
     * イベント一覧
     */
    public function index(): View
    {
        // 各イベントの予約人数（キャンセル済みを除く）を、SQL 1回でまとめて合計する（N+1 対策）
        $events = Event::withSum(
            ['reservations' => fn ($query) => $query->whereNull('cancelled_at')],
            'guests'
        )->orderBy('starts_at')->paginate(10);

        return view('events.index', compact('events'));
    }

    /**
     * イベント作成フォームの表示
     */
    public function create(): View
    {
        return view('events.create');
    }

    /**
     * イベントの保存
     */
    public function store(StoreEventRequest $request): RedirectResponse
    {
        $event = Event::create($request->validated());

        return redirect()
            ->route('events.show', $event)
            ->with('status', 'イベントを作成しました。');
    }

    /**
     * イベント詳細
     */
    public function show(Event $event): View
    {
        return view('events.show', compact('event'));
    }
}
