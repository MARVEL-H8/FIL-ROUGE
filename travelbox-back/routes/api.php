<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\Admin\UserManagementController;
use App\Http\Controllers\API\Admin\ListingManagementController;
use App\Http\Controllers\API\PackageListingController;
use App\Http\Controllers\API\TravelListingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes d'authentification
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/admin/login', [AuthController::class, 'adminLogin']); // Route de connexion admin

// Routes de réinitialisation du mot de passe (ne nécessitent pas d'authentification)
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Routes protégées par Sanctum pour les utilisateurs
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('/auth/password', [AuthController::class, 'changePassword']);
    
    // Listings routes
    Route::prefix('listings')->group(function () {
        // My listings
        Route::get('/travel/my', [TravelListingController::class, 'myListings']);
        Route::get('/package/my', [PackageListingController::class, 'myListings']);
        
        // Travel listings
        Route::post('/travel', [TravelListingController::class, 'store']);
        Route::put('/travel/{id}', [TravelListingController::class, 'update']);
        Route::delete('/travel/{id}', [TravelListingController::class, 'destroy']);
        Route::put('/travel/{id}/toggle', [TravelListingController::class, 'toggleActive']);
        
        // Package listings
        Route::post('/package', [PackageListingController::class, 'store']);
        Route::put('/package/{id}', [PackageListingController::class, 'update']);
        Route::delete('/package/{id}', [PackageListingController::class, 'destroy']);
        Route::put('/package/{id}/toggle', [PackageListingController::class, 'toggleActive']);
    });
});

// Routes protégées par Sanctum pour les administrateurs
// Utiliser uniquement auth:sanctum sans le middleware ability qui cause des erreurs
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    // Gestion des utilisateurs
    Route::get('/users', [UserManagementController::class, 'getAllUsers']);
    Route::get('/users/active', [UserManagementController::class, 'getActiveUsers']);
    Route::post('/users', [UserManagementController::class, 'createUser']);
    Route::put('/users/{id}/toggle', [UserManagementController::class, 'toggleUserStatus']);
    
    // Gestion des annonces
    Route::get('/listings', [ListingManagementController::class, 'getAllListings']);
    Route::put('/listings/toggle', [ListingManagementController::class, 'toggleListingStatus']);
    Route::post('/listings/clean-expired', [ListingManagementController::class, 'cleanExpiredListings']);
});

// Routes publiques pour les listings
Route::prefix('listings')->group(function () {
    Route::get('/travel', [TravelListingController::class, 'index']);
    Route::get('/travel/{id}', [TravelListingController::class, 'show']);
    Route::get('/package', [PackageListingController::class, 'index']);
    Route::get('/package/{id}', [PackageListingController::class, 'show']);
});
