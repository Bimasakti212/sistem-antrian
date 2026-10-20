<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->middleware('auth');
Route::get('/antrian', [QueueController::class, 'publicForm'])->name('queues.public');
Route::post('/antrian', [QueueController::class, 'storePublic'])->middleware('throttle:10,1')->name('queues.public.store');
Route::get('/antrian/{queue}/hasil', [QueueController::class, 'result'])->middleware('signed')->name('queues.result');
Route::get('/login', [AuthController::class, 'form'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/dashboard', [QueueController::class, 'dashboard'])->name('dashboard');
    Route::post('/dashboard/queues', [QueueController::class, 'storeManual'])->name('queues.manual');
    Route::post('/dashboard/queues/next', [QueueController::class, 'callNext'])->name('queues.next');
    Route::post('/dashboard/queues/{queue}/{action}', [QueueController::class, 'action'])
        ->whereIn('action', ['recall', 'serve', 'skip', 'complete'])->name('queues.action');
    Route::get('/riwayat', [QueueController::class, 'history'])->name('queues.history');
    Route::get('/qr', [QueueController::class, 'qr'])->name('queues.qr');

    Route::middleware('can:admin')->group(function () {
        Route::get('/kategori', [QueueController::class, 'categories'])->name('queues.categories');
        Route::get('/loket', [QueueController::class, 'counters'])->name('queues.counters');
        Route::post('/loket', [QueueController::class, 'storeCounter'])->name('queues.counters.store');
        Route::put('/loket/{counter}', [QueueController::class, 'updateCounter'])->name('queues.counters.update');
        Route::get('/petugas', [QueueController::class, 'staff'])->name('queues.staff');
        Route::post('/petugas', [QueueController::class, 'storeStaff'])->name('queues.staff.store');
        Route::post('/kategori', [QueueController::class, 'storeCategory'])->name('queues.categories.store');
        Route::put('/kategori/{category}', [QueueController::class, 'updateCategory'])->name('queues.categories.update');
    });
});
