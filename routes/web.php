<?php

use App\Http\Controllers\Admin\BlockedKeywordController as AdminBlockedKeywordController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GenreController as AdminGenreController;
use App\Http\Controllers\Admin\ModerationController as AdminModerationController;
use App\Http\Controllers\Admin\MovieController as AdminMovieController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MoviePublicController;
use App\Http\Controllers\MyReviewsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/movies', [MoviePublicController::class, 'index'])->name('movies.index');
Route::get('/movies/{movie:slug}', [MoviePublicController::class, 'show'])->name('movies.show');

// Public profile (read-only)
Route::get('/u/{user}', [ProfileController::class, 'show'])->name('users.show');

// Dev-only design system preview (only registered in non-production)
if (! app()->environment('production')) {
    Route::inertia('/dev/design', 'dev/Design')->name('dev.design');
}

Route::middleware(['auth', 'verified'])->group(function () {
    // /dashboard tidak dipakai — redirect ke home publik
    Route::redirect('/dashboard', '/')->name('dashboard');
    Route::post('/reviews', [ReviewController::class, 'store'])
        ->middleware('throttle:review-submit')
        ->name('reviews.store');
    Route::post('/reviews/{review}/report', [ReviewReportController::class, 'store'])
        ->middleware('throttle:review-report')
        ->name('reviews.report');
    Route::get('/my-reviews', [MyReviewsController::class, 'index'])->name('my-reviews.index');
    Route::delete('/my-reviews/{review}', [MyReviewsController::class, 'destroy'])->name('my-reviews.destroy');
});

// Admin area — must be authenticated AND have role=admin
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified', 'admin'])
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::resource('movies', AdminMovieController::class)
            ->except(['show'])
            ->parameters(['movies' => 'movie']);
        Route::resource('genres', AdminGenreController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('keywords', AdminBlockedKeywordController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['keywords' => 'blocked_keyword']);

        // Moderation queue (pending reviews)
        Route::get('moderation', [AdminModerationController::class, 'index'])->name('moderation.index');
        Route::post('moderation/{review}/approve', [AdminModerationController::class, 'approve'])->name('moderation.approve');
        Route::post('moderation/{review}/reject', [AdminModerationController::class, 'reject'])->name('moderation.reject');

        // Reports queue
        Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::post('reports/{report}/hide', [AdminReportController::class, 'hide'])->name('reports.hide');
        Route::post('reports/{report}/dismiss', [AdminReportController::class, 'dismiss'])->name('reports.dismiss');
    });

require __DIR__.'/settings.php';
