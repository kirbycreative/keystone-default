<?php

use App\Http\Controllers\Admin\ContentAssetController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaLibraryController;
use App\Http\Controllers\Admin\OnboardingController;
use App\Http\Controllers\Admin\PageSuggestionController;
use App\Http\Controllers\Admin\SiteSettingsController;
use App\Http\Controllers\Admin\SiteStructureController;
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

Route::middleware(['auth', 'onboarded'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::get('/onboarding/check-dns', [OnboardingController::class, 'checkDNS'])->name('onboarding.check-dns');
    Route::post('/onboarding/brand', [OnboardingController::class, 'saveBrand'])->name('onboarding.brand');
    Route::get('/onboarding/suggest-sites', [OnboardingController::class, 'suggestSites'])->name('onboarding.suggest-sites');
    Route::post('/onboarding/inspiration', [OnboardingController::class, 'saveInspiration'])->name('onboarding.inspiration');
    Route::post('/onboarding/assets', [ContentAssetController::class, 'dropUpload'])->name('onboarding.assets');
    Route::post('/onboarding/materials', [OnboardingController::class, 'saveMaterials'])->name('onboarding.materials');
    Route::post('/onboarding', [OnboardingController::class, 'complete'])->name('onboarding.complete');
    Route::post('/onboarding/style-guide-decision', [OnboardingController::class, 'styleGuideDecision'])->name('onboarding.style-guide-decision');
    Route::post('/onboarding/page-tree-decision', [OnboardingController::class, 'pageTreeDecision'])->name('onboarding.page-tree-decision');

    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/build-status', [DashboardController::class, 'status'])->name('build-status');
    Route::get('/media-library', [MediaLibraryController::class, 'index'])->name('media-library.index');
    Route::post('/media-library', [MediaLibraryController::class, 'store'])
        ->middleware('site-editor')->name('media-library.store');
    Route::middleware('page-tree-approved')->group(function (): void {
        Route::get('/content', [ContentAssetController::class, 'index'])->name('content.index');
        Route::post('/content', [ContentAssetController::class, 'store'])
            ->middleware('site-editor')->name('content.store');
        Route::post('/content/drop', [ContentAssetController::class, 'dropUpload'])
            ->middleware('site-editor')->name('content.drop');
        Route::get('/content/review', [ContentAssetController::class, 'review'])->name('content.review');
        Route::get('/content/{contentAsset}/download', [ContentAssetController::class, 'download'])
            ->name('content.download');
    });
    Route::get('/page-suggestions', [PageSuggestionController::class, 'index'])->name('page-suggestions.index');
    Route::post('/page-suggestions/generate', [PageSuggestionController::class, 'generate'])
        ->middleware('site-editor')->name('page-suggestions.generate');
    Route::patch('/page-suggestions/{pageSuggestion}/status', [PageSuggestionController::class, 'updateStatus'])
        ->middleware('site-editor')->name('page-suggestions.status');
    Route::patch('/page-suggestions/{pageSuggestion}/feedback', [PageSuggestionController::class, 'feedback'])
        ->middleware('site-editor')->name('page-suggestions.feedback');
    Route::get('/site-settings', [SiteSettingsController::class, 'show'])->name('site-settings');
    Route::get('/site-preview', [SiteController::class, 'preview'])->name('site-preview');
    Route::patch('/site-settings/identity', [SiteSettingsController::class, 'identity'])->name('site-settings.identity');
    Route::patch('/site-settings/seo', [SiteSettingsController::class, 'seo'])->name('site-settings.seo');
    Route::post('/site-settings/publish', [SiteSettingsController::class, 'publish'])->name('site-settings.publish');
    Route::patch('/site-settings/features', [SiteSettingsController::class, 'features'])->name('site-settings.features');
    Route::patch('/site-settings/integrations', [SiteSettingsController::class, 'integrations'])
        ->name('site-settings.integrations');
    Route::post('/site-settings/versions/{version}/rollback', [SiteSettingsController::class, 'rollback'])
        ->name('site-settings.rollback');
    Route::get('/site-structure', [SiteStructureController::class, 'show'])->name('site-structure');
    Route::put('/site-structure/pages', [SiteStructureController::class, 'page'])->name('site-structure.page');
    Route::delete('/site-structure/pages/{page}', [SiteStructureController::class, 'deletePage'])
        ->name('site-structure.page.delete');
    Route::put('/site-structure/sections', [SiteStructureController::class, 'section'])->name('site-structure.section');
    Route::put('/site-structure/navigation', [SiteStructureController::class, 'navigation'])
        ->name('site-structure.navigation');
    Route::delete('/site-structure/pages/{page}/sections/{section}', [SiteStructureController::class, 'deleteSection'])
        ->name('site-structure.section.delete');
});

Route::get('/{path}', [SiteController::class, 'public'])
    ->where('path', '.*')
    ->name('site.page');

// Template Viewer Routes
Route::middleware(['auth', 'onboarded'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/templates', [TemplateViewerController::class, 'index'])->name('templates.index');
    Route::get('/templates/{path}', [TemplateViewerController::class, 'show'])->name('templates.show');
});

Route::get('/{path}', [SiteController::class, 'public'])
    ->where('path', '.*')
    ->name('site.page');

Route::get('/{path}', [SiteController::class, 'public'])
    ->where('path', '.*')
    ->name('site.page');
