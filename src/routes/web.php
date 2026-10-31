<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SupportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// 掲示板
Route::get('/comments', [CommentController::class, 'index'])
    ->name('comments.index');

Route::post('/comments', [CommentController::class, 'store'])
    ->middleware(['auth', 'throttle:10,1'])
    ->name('comments.store');

Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
    ->middleware('auth')
    ->name('comments.destroy');

// イベント予約
Route::resource('events', EventController::class)->only(['index', 'create', 'store', 'show']);

Route::get('events/{event}/reservations/create', [ReservationController::class, 'create'])
    ->name('reservations.create');
Route::post('events/{event}/reservations', [ReservationController::class, 'store'])
    ->name('reservations.store');
Route::get('reservations', [ReservationController::class, 'index'])
    ->name('reservations.index');
Route::patch('reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])
    ->name('reservations.cancel');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('posts', PostController::class);

    // 投げ銭（Stripe 決済）
    Route::post('/posts/{post}/support', [SupportController::class, 'create'])->name('supports.create');
    Route::get('/supports/success', [SupportController::class, 'success'])->name('supports.success');
});

require __DIR__.'/auth.php';