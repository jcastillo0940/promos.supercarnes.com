<?php

use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\PublicSettingsController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('invoices/resolve', [InvoiceController::class, 'resolve'])->middleware('throttle:invoice-scan');
Route::post('invoices/scan', [InvoiceController::class, 'store'])->middleware('throttle:invoice-scan');

Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:invoice-scan');
Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:invoice-scan');
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/dream-profile', [AuthController::class, 'dreamProfile']);
});

Route::get('campaigns', [CampaignController::class, 'index']);
Route::get('campaigns/{slug}', [CampaignController::class, 'show']);
Route::get('campaigns/{slug}/progress', [CampaignController::class, 'progress']);

Route::get('public/settings', [PublicSettingsController::class, 'index']);
Route::get('public/branches', [PublicSettingsController::class, 'branches']);
