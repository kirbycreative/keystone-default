<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\FormSubmissionController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\TemplateViewerController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'public'])->name('home');
Route::get('/robots.txt', [SiteController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SiteController::class, 'sitemap'])->name('sitemap');
Route::post('/forms/{section}', [FormSubmissionController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('forms.store');
Route::get('/robots.txt', [SiteController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SiteController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SiteController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SiteController::class, 'sitemap'])->name('sitemap');

Route::get('/health', function () {
    DB::select('select 1');

    return response()->json(['status' => 'ok']);
})->name('health');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'mfa', 'onboarded'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/site-preview', [SiteController::class, 'preview'])->name('site-preview');
});

Route::get('/{path}', [SiteController::class, 'public'])
    ->where('path', '.*')
    ->name('site.page');

// Template Viewer Routes
Route::middleware(['auth', 'mfa', 'onboarded'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/templates', [TemplateViewerController::class, 'index'])->name('templates.index');
    Route::get('/templates/{path}', [TemplateViewerController::class, 'show'])->name('templates.show');
});

Route::get('/{path}', [SiteController::class, 'public'])
    ->where('path', '.*')
    ->name('site.page');

Route::get('/{path}', [SiteController::class, 'public'])
    ->where('path', '.*')
    ->name('site.page');
