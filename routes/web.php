<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\QueueController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/antrian');
Route::get('/antrian', [QueueController::class, 'publicForm'])->name('queues.public');
Route::post('/antrian', [QueueController::class, 'storePublic'])->middleware('throttle:10,1')->name('queues.public.store');
Route::get('/antrian/{queue}/hasil', [QueueController::class, 'result'])->name('queues.result');
Route::get('/login', [AuthController::class, 'form'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [QueueController::class, 'dashboard'])->name('dashboard');
    Route::post('/dashboard/queues', [QueueController::class, 'storeManual'])->name('queues.manual');
    Route::post('/dashboard/queues/next', [QueueController::class, 'callNext'])->name('queues.next');
    Route::post('/dashboard/queues/{queue}/{action}', [QueueController::class, 'action'])->whereIn('action', ['recall', 'skip', 'complete'])->name('queues.action');
    Route::get('/riwayat', [QueueController::class, 'history'])->name('queues.history');
    Route::get('/qr', [QueueController::class, 'qr'])->name('queues.qr');
});
