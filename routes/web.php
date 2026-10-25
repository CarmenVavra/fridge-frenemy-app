<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ButlerController;
use App\Http\Controllers\FridgeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;

Route::get('/impressum', [LegalController::class, 'imprint'])->name('legal.imprint');
Route::get('/datenschutz', [LegalController::class, 'privacy'])->name('legal.privacy');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/registrieren', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/registrieren', [RegisteredUserController::class, 'store'])->middleware('throttle:10,1');

    Route::get('/passwort-vergessen', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/passwort-vergessen', [PasswordResetLinkController::class, 'store'])->middleware('throttle:6,1')->name('password.email');

    Route::get('/passwort-zuruecksetzen/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/passwort-zuruecksetzen', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::redirect('/', '/fach');

    Route::get('/fach', [FridgeController::class, 'index'])->name('fridge.index');
    Route::post('/fach/artikel', [FridgeController::class, 'store'])->name('fridge.store');
    Route::patch('/fach/artikel/{item}/aufgegessen', [FridgeController::class, 'consume'])
        ->middleware('can:update,item')->name('fridge.consume');
    Route::patch('/fach/artikel/{item}/weggeworfen', [FridgeController::class, 'trash'])
        ->middleware('can:update,item')->name('fridge.trash');
    Route::patch('/fach/artikel/{item}/haltbarkeit', [FridgeController::class, 'updateExpiry'])
        ->middleware('can:update,item')->name('fridge.expiry');

    Route::get('/scan', [ScanController::class, 'index'])->name('scan.index');
    Route::post('/api/scan-barcode', [ScanController::class, 'lookup'])
        ->middleware('throttle:30,1')->name('scan.lookup');
    Route::post('/api/datum-erkennen', [ScanController::class, 'detectDate'])
        ->middleware('throttle:120,1')->name('scan.date');

    Route::get('/butler', [ButlerController::class, 'index'])->name('butler.index');
    Route::put('/butler/tonalitaet', [ButlerController::class, 'updateTone'])->name('butler.tone');
    Route::post('/butler/alarm-testen', [ButlerController::class, 'testAlert'])
        ->middleware('throttle:10,1')->name('butler.test');
    Route::patch('/butler/alarme/{alert}/gelesen', [ButlerController::class, 'dismissAlert'])
        ->middleware('can:update,alert')->name('butler.alerts.dismiss');

    Route::get('/bilanz', [StatsController::class, 'index'])->name('stats.index');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil/passwort', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('/profil/haushalt', [ProfileController::class, 'updateHousehold'])->name('profile.household');
    Route::post('/profil/haushalt/neuer-code', [ProfileController::class, 'regenerateInviteCode'])
        ->middleware('throttle:10,1')->name('profile.invite-code');
    Route::post('/profil/haushalt/beitreten', [ProfileController::class, 'joinHousehold'])
        ->middleware('throttle:10,1')->name('profile.join');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
