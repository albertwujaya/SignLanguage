<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GestureController as AdminGestureController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GestureController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'app')->name('home');
Route::get('/api/gestures', [GestureController::class, 'index'])->name('gestures.index');
Route::get('/api/gestures/{slug}', [GestureController::class, 'show'])->name('gestures.show');
Route::get('/api/stats', [GestureController::class, 'stats'])->name('gestures.stats');

Route::get('/setup', [AuthController::class, 'setup'])->name('setup');
Route::post('/setup', [AuthController::class, 'storeFirstAdmin'])
    ->middleware('throttle:3,1')
    ->name('setup.store');

Route::middleware('guest')->group(function (): void {
    Route::view('/daftar', 'auth.register')->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])
        ->middleware('throttle:5,1')
        ->name('register.store');

    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/profil', [ProfileController::class, 'show'])->name('profile');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/api/progress', [ProgressController::class, 'index'])->name('progress.index');
    Route::put('/api/progress/{slug}/learned', [ProgressController::class, 'learn'])->name('progress.learn');
    Route::delete('/api/progress/{slug}/learned', [ProgressController::class, 'unlearn'])->name('progress.unlearn');
    Route::post('/api/practice-sessions', [ProgressController::class, 'storeSession'])->name('practice-sessions.store');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:access-admin'])->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Gesture Quality, Analytics, Preview, Trash
    Route::get('/gestures/completeness', [AdminGestureController::class, 'completeness'])->name('gestures.completeness');
    Route::get('/gestures/analytics', [AdminGestureController::class, 'analytics'])->name('gestures.analytics');
    Route::post('/gestures/{gesture}/toggle-status', [AdminGestureController::class, 'toggleStatus'])->name('gestures.toggle-status');
    Route::get('/gestures/{gesture}/preview', [AdminGestureController::class, 'preview'])->name('gestures.preview');

    Route::middleware('can:delete-gestures')->group(function (): void {
        Route::get('/gestures/trash', [AdminGestureController::class, 'trash'])->name('gestures.trash');
        Route::post('/gestures/{id}/restore', [AdminGestureController::class, 'restore'])->name('gestures.restore');
        Route::delete('/gestures/{id}/force-delete', [AdminGestureController::class, 'forceDelete'])->name('gestures.force-delete');
    });

    Route::resource('gestures', AdminGestureController::class)
        ->except('show')
        ->middlewareFor('destroy', 'can:delete-gestures');

    // Users & Progress Management
    Route::middleware('can:manage-users')->group(function (): void {
        Route::get('/users/export/csv', [UserController::class, 'exportCsv'])->name('users.export');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Admin Activity Logs
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
});
