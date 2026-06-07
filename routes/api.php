<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BadgeController;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\ModerationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\VoteController;
use Illuminate\Support\Facades\Route;

/**
 * --- Rute Publik (Tanpa Autentikasi) ---
 */
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Eksplorasi Postingan, Kategori, dan Tag
Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/trending', [PostController::class, 'trending']);
Route::get('/posts/{id}', [PostController::class, 'show']);
Route::get('/posts/{id}/history', [PostController::class, 'history']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/tags', TagController::class);

// Komentar dan Profil
Route::get('/comments', [CommentController::class, 'index']);
Route::get('/comments/{id}/history', [CommentController::class, 'history']);
Route::get('/leaderboard', [ProfileController::class, 'leaderboard']);
Route::get('/profiles/{username}', [ProfileController::class, 'show']);
Route::get('/profiles/{username}/posts', [ProfileController::class, 'posts']);
Route::get('/profiles/{username}/followers', [FollowController::class, 'followers']);
Route::get('/profiles/{username}/following', [FollowController::class, 'following']);

/**
 * --- Rute yang Membutuhkan Autentikasi (Sanctum) ---
 */
Route::middleware(['auth:sanctum', 'check.banned'])->group(function () {
    // Autentikasi & Akun
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Manajemen Postingan (CRUD)
    Route::post('/posts', [PostController::class, 'store']);
    Route::put('/posts/{id}', [PostController::class, 'update']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);

    // Manajemen Komentar
    Route::post('/comments', [CommentController::class, 'store']);
    Route::put('/comments/{id}', [CommentController::class, 'update']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);
    Route::post('/comments/{id}/accept', [CommentController::class, 'accept']);

    // Sistem Vote & Like
    Route::post('/vote', [VoteController::class, 'vote']);
    Route::post('/like', [LikeController::class, 'toggle']);

    // Bookmark & Badge
    Route::get('/bookmarks', [BookmarkController::class, 'index']);
    Route::post('/bookmarks', [BookmarkController::class, 'toggle']);
    Route::get('/badges', [BadgeController::class, 'index']);
    Route::get('/my-badges', [BadgeController::class, 'myBadges']);
    Route::get('/reputation-history', [BadgeController::class, 'reputationHistory']);

    // Notifikasi
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // Profil & Sosialisasi
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::get('/profile/stats', [ProfileController::class, 'stats']);
    Route::post('/follow', [FollowController::class, 'toggle']);
    Route::post('/reports', [ReportController::class, 'store']);

    /**
     * --- Rute Khusus Moderator ---
     */
    Route::middleware('moderator')->group(function () {
        Route::get('/moderation/reports', [ModerationController::class, 'reports']);
        Route::post('/moderation/reports/{id}/resolve', [ModerationController::class, 'resolveReport']);
        Route::post('/moderation/ban', [ModerationController::class, 'banUser']);
        Route::post('/moderation/warn', [ModerationController::class, 'warnUser']);
        Route::post('/moderation/unban', [ModerationController::class, 'unbanUser']);
        Route::get('/moderation/logs', [ModerationController::class, 'logs']);
    });

    /**
     * --- Rute Khusus Administrator ---
     */
    Route::middleware('admin')->group(function () {
        Route::get('/admin/users', [\App\Http\Controllers\Api\UserController::class, 'index']);
        Route::post('/admin/users/{id}/roles', [\App\Http\Controllers\Api\UserController::class, 'updateRole']);

        // Manajemen Kategori oleh Admin
        Route::post('/admin/categories', [CategoryController::class, 'store']);
        Route::put('/admin/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/admin/categories/{id}', [CategoryController::class, 'destroy']);
    });
});
