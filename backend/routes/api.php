<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ContentController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\InquiryController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\MyPropertyController;
use App\Http\Controllers\Api\V1\PropertyController;
use App\Http\Controllers\Api\V1\PropertyImageController;
use App\Http\Controllers\Api\V1\RequirementController;
use App\Http\Controllers\Api\V1\ToolsController;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 routes  —  all under /api/v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Health / version probe.
    Route::get('/', fn () => ApiResponse::success([
        'name'    => config('app.name'),
        'version' => 'v1',
        'time'    => now()->toIso8601String(),
    ], 'Aakash Realtor API'));

    /* ── Auth ──────────────────────────────────────────────────────── */
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:auth');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:auth');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    /* ── Public reference data ─────────────────────────────────────── */
    Route::get('cities', [LookupController::class, 'cities']);
    Route::get('cities/{city}/areas', [LookupController::class, 'areas']);
    Route::get('categories', [LookupController::class, 'categories']);
    Route::get('amenities', [LookupController::class, 'amenities']);

    /* ── Properties (public) ───────────────────────────────────────── */
    Route::get('properties', [PropertyController::class, 'index']);

    // Homepage section endpoints (must precede the {slug} catch-all).
    Route::get('properties/featured', [PropertyController::class, 'featured']);
    Route::get('properties/exclusive', [PropertyController::class, 'exclusive']);
    Route::get('properties/emerging', [PropertyController::class, 'emerging']);
    Route::get('properties/open-house', [PropertyController::class, 'openHouse']);
    Route::get('properties/by-owner', [PropertyController::class, 'byOwner']);
    Route::get('properties/sold', [PropertyController::class, 'sold']);

    Route::get('properties/{slug}', [PropertyController::class, 'show']);

    /* ── Leads (public, throttled + honeypot) ──────────────────────── */
    Route::post('inquiries', [InquiryController::class, 'store'])
        ->middleware(['throttle:leads', 'honeypot']);

    Route::get('requirements', [RequirementController::class, 'index']);
    Route::post('requirements', [RequirementController::class, 'store'])
        ->middleware(['throttle:leads', 'honeypot']);

    /* ── Tools (public) ────────────────────────────────────────────── */
    Route::prefix('tools')->group(function () {
        Route::post('emi', [ToolsController::class, 'emi']);
        Route::get('land-units', [ToolsController::class, 'landUnitList']);
        Route::post('land-converter', [ToolsController::class, 'landUnits']);
        Route::post('date-converter', [ToolsController::class, 'dateConvert']);
    });

    /* ── Content (public) ──────────────────────────────────────────── */
    Route::get('blogs', [ContentController::class, 'blogs']);
    Route::get('blogs/{blog}', [ContentController::class, 'blog']);
    Route::get('faqs', [ContentController::class, 'faqs']);
    Route::get('branches', [ContentController::class, 'branches']);
    Route::get('team', [ContentController::class, 'team']);

    /* ── Authenticated (Sanctum + active account) ──────────────────── */
    Route::middleware(['auth:sanctum', 'active'])->group(function () {

        // Listing CRUD (ownership enforced by PropertyPolicy in controller).
        Route::post('properties', [PropertyController::class, 'store']);
        Route::put('properties/{property}', [PropertyController::class, 'update']);
        Route::patch('properties/{property}', [PropertyController::class, 'update']);
        Route::delete('properties/{property}', [PropertyController::class, 'destroy']);

        // My listings dashboard.
        Route::get('my/properties', [MyPropertyController::class, 'index']);

        // Property media.
        Route::middleware('throttle:uploads')->group(function () {
            Route::post('properties/{property}/images', [PropertyImageController::class, 'store']);
            Route::patch('properties/{property}/images/reorder', [PropertyImageController::class, 'reorder']);
        });
        Route::delete('property-images/{image}', [PropertyImageController::class, 'destroy']);
        Route::patch('property-images/{image}/primary', [PropertyImageController::class, 'setPrimary']);

        // Favorites.
        Route::get('favorites', [FavoriteController::class, 'index']);
        Route::post('favorites/{property}', [FavoriteController::class, 'toggle']);

        /* ── Staff (agent/admin) lead management ───────────────────── */
        Route::middleware('role:agent,admin')->prefix('admin')->group(function () {
            Route::get('inquiries', [InquiryController::class, 'index']);
            Route::patch('inquiries/{inquiry}', [InquiryController::class, 'update']);
        });
    });
});

// Fallback for unknown API routes -> JSON 404 (not the HTML page).
Route::fallback(fn () => ApiResponse::error('Endpoint not found.', 404));
