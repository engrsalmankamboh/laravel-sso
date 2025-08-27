<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeepLinkController;

/*
|--------------------------------------------------------------------------
| SSO Authentication Routes
|--------------------------------------------------------------------------
|
| These routes handle Social Single Sign-On authentication flows
| for Google, Facebook, and Apple across web, iOS, and Android platforms.
|
*/

// OAuth Redirect Routes
Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('/google', [AuthController::class, 'redirectToGoogle'])->name('google');
    Route::get('/facebook', [AuthController::class, 'redirectToFacebook'])->name('facebook');
    Route::get('/apple', [AuthController::class, 'redirectToApple'])->name('apple');
});

// OAuth Callback Routes
Route::prefix('social')->name('auth.')->group(function () {
    Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('google.callback');
    Route::get('/facebook/callback', [AuthController::class, 'handleFacebookCallback'])->name('facebook.callback');
    Route::post('/apple/callback', [AuthController::class, 'handleAppleCallback'])->name('apple.callback');
});

// Deep Link Management Routes (Optional)
Route::prefix('api/deep-links')->name('api.deep-links.')->group(function () {
    Route::get('/generate', [DeepLinkController::class, 'generateDeepLinks'])->name('generate');
    Route::post('/validate', [DeepLinkController::class, 'validateDeepLink'])->name('validate');
    Route::post('/extract', [DeepLinkController::class, 'extractDeepLinkData'])->name('extract');
});

// Authentication Management Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Mobile-specific routes
    Route::get('/mobile/dashboard', function () {
        return view('mobile.dashboard');
    })->name('mobile.dashboard');
});

// Protected Routes (require authentication)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    Route::get('/profile', function () {
        return view('profile');
    })->name('profile');
});

// Public Routes
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

/*
|--------------------------------------------------------------------------
| Platform-Specific Route Groups
|--------------------------------------------------------------------------
|
| You can also organize routes by platform if needed
|
*/

// Web Platform Routes
Route::prefix('web')->middleware('web')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('web.login');
});

// Mobile Platform Routes
Route::prefix('mobile')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('mobile.login');
    Route::get('/dashboard', function () {
        return view('mobile.dashboard');
    })->name('mobile.dashboard');
});

/*
|--------------------------------------------------------------------------
| API Routes for Mobile Apps
|--------------------------------------------------------------------------
|
| These routes provide JSON responses for mobile applications
|
*/

Route::prefix('api/v1')->name('api.v1.')->group(function () {
    // Authentication API
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/google', [AuthController::class, 'handleGoogleCallback'])->name('google');
        Route::post('/facebook', [AuthController::class, 'handleFacebookCallback'])->name('facebook');
        Route::post('/apple', [AuthController::class, 'handleAppleCallback'])->name('apple');
    });
    
    // Deep Link API
    Route::prefix('deep-links')->name('deep-links.')->group(function () {
        Route::get('/{provider}', [DeepLinkController::class, 'generateDeepLinks'])->name('generate');
        Route::post('/validate', [DeepLinkController::class, 'validateDeepLink'])->name('validate');
        Route::post('/extract', [DeepLinkController::class, 'extractDeepLinkData'])->name('extract');
    });
    
    // User Management API
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function () {
            return auth()->user();
        })->name('user.profile');
        
        Route::post('/logout', function () {
            auth()->user()->tokens()->delete();
            return response()->json(['message' => 'Logged out successfully']);
        })->name('user.logout');
    });
});

/*
|--------------------------------------------------------------------------
| Fallback Route
|--------------------------------------------------------------------------
|
| Handle deep link fallbacks for mobile apps
|
*/

Route::fallback(function () {
    // Check if this is a mobile deep link request
    $userAgent = request()->userAgent();
    $isMobile = preg_match('/(android|iphone|ipad|ipod)/i', $userAgent);
    
    if ($isMobile) {
        // Redirect to mobile app or show mobile-specific page
        return response()->json([
            'error' => 'Route not found',
            'platform' => 'mobile',
            'message' => 'This route is not available on mobile'
        ], 404);
    }
    
    // For web, show 404 page
    abort(404);
});
