<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CommissionController;
use App\Http\Middleware\SimpleAuth;


Route::get('/login', [CommissionController::class, 'loginForm'])->name('login');
Route::post('/login', [CommissionController::class, 'login'])->name('login.post');
Route::get('/logout', [CommissionController::class, 'logout'])->name('logout');


Route::middleware([SimpleAuth::class])->group(function () {
    Route::get('/', [CommissionController::class, 'index'])->name('home');
    Route::post('/pay/{commissionId}', [CommissionController::class, 'pay'])->name('pay');
});
