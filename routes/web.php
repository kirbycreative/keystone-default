<?php

use App\Http\Controllers\Admin\ContentAssetController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OnboardingController;
use App\Http\Controllers\Admin\PageSuggestionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\TemplateViewerController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

Route::view('/', 'welcome')->name('home');
Route::get('/health', function () {
    DB::select('select 1');
    Storage::disk(config('filesystems.default'))->put('.healthcheck', now()->toIso8601String());
    Storage::disk(config('filesystems.default'))->delete('.healthcheck');

    return response()->json(['status' => 'ok']);
})->name('health');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'email'])->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\Auth\PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'update'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'onboarded'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::get('/onboarding/check-dns', [OnboardingController::class, 'checkDNS'])->name('onboarding.check-dns');
    Route::post('/onboarding/brand', [OnboardingController::class, 'saveBrand'])->name('onboarding.brand');
    Route::get('/onboarding/suggest-sites', [OnboardingController::class, 'suggestSites'])->name('onboarding.suggest-sites');
    Route::post('/onboarding/inspiration', [OnboardingController::class, 'saveInspiration'])->name('onboarding.inspiration');
    Route::post('/onboarding', [OnboardingController::class, 'complete'])->name('onboarding.complete');

    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/content', [ContentAssetController::class, 'index'])->name('content.index');
    Route::post('/content', [ContentAssetController::class, 'store'])->name('content.store');
    Route::post('/content/drop', [ContentAssetController::class, 'dropUpload'])->name('content.drop');
    Route::get('/content/review', [ContentAssetController::class, 'review'])->name('content.review');
    Route::get('/content/{contentAsset}/download', [ContentAssetController::class, 'download'])
        ->name('content.download');
    Route::get('/page-suggestions', [PageSuggestionController::class, 'index'])->name('page-suggestions.index');
    Route::post('/page-suggestions/generate', [PageSuggestionController::class, 'generate'])->name('page-suggestions.generate');
    Route::patch('/page-suggestions/{pageSuggestion}/status', [PageSuggestionController::class, 'updateStatus'])
        ->name('page-suggestions.status');
    Route::patch('/page-suggestions/{pageSuggestion}/feedback', [PageSuggestionController::class, 'feedback'])
        ->name('page-suggestions.feedback');
});

// Template Viewer Routes
Route::middleware(['auth', 'onboarded'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/templates', [TemplateViewerController::class, 'index'])->name('templates.index');
    Route::get('/templates/{path}', [TemplateViewerController::class, 'show'])->name('templates.show');
});
