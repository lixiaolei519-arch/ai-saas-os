<?php

use App\Http\Controllers\Api\V1\AdminAuthController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AiUsageController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AuthorizationController;
use App\Http\Controllers\Api\V1\CustomerPortalController;
use App\Http\Controllers\Api\V1\LicenseController;
use App\Http\Controllers\Api\V1\MarketingController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentCallbackController;
use App\Http\Controllers\Api\V1\PluginController;
use App\Http\Controllers\Api\V1\ProductPlanController;
use App\Http\Controllers\Api\V1\RiskController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::prefix('portal')->group(function () {
            Route::get('me', [CustomerPortalController::class, 'me']);
            Route::get('dashboard', [CustomerPortalController::class, 'dashboard']);
            Route::get('licenses', [CustomerPortalController::class, 'licenses']);
            Route::get('orders', [CustomerPortalController::class, 'orders']);
            Route::get('ai-account', [CustomerPortalController::class, 'aiAccount']);
            Route::get('usage-records', [CustomerPortalController::class, 'usageRecords']);
            Route::get('promotion-links', [CustomerPortalController::class, 'promotionLinks']);
            Route::get('referrals', [CustomerPortalController::class, 'referrals']);
            Route::get('commissions', [CustomerPortalController::class, 'commissions']);
            Route::post('renewals', [CustomerPortalController::class, 'requestRenewal']);
            Route::get('licenses/{license}/key', [CustomerPortalController::class, 'copyLicenseKey']);
            Route::delete('licenses/{license}/domain', [CustomerPortalController::class, 'unbindDomain']);
        });
    });

    Route::post('admin/auth/login', [AdminAuthController::class, 'login']);
    Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
        Route::get('users', [AdminController::class, 'users']);
        Route::get('tenants', [AdminController::class, 'tenants']);
        Route::get('licenses', [AdminController::class, 'licenses']);
        Route::get('orders', [AdminController::class, 'orders']);
        Route::get('payment-callbacks', [AdminController::class, 'paymentCallbacks']);
        Route::get('marketing/channels', [AdminController::class, 'channels']);
        Route::get('marketing/commissions', [AdminController::class, 'commissions']);
        Route::get('ai/usage-records', [AdminController::class, 'aiUsageRecords']);
        Route::get('stats', [AdminController::class, 'stats']);
        Route::get('dashboard', [AdminController::class, 'dashboard']);
        Route::get('system', [AdminController::class, 'system']);
    });

    Route::post('tenants', [TenantController::class, 'store']);
    Route::post('permissions', [AuthorizationController::class, 'createPermission']);
    Route::post('roles', [AuthorizationController::class, 'createRole']);
    Route::post('roles/{role}/permissions', [AuthorizationController::class, 'attachPermission']);
    Route::post('tenants/{tenant}/users/{user}/roles', [AuthorizationController::class, 'assignRole']);
    Route::get('tenants/{tenant}/users/{user}/permissions/{permission}', [AuthorizationController::class, 'check']);

    Route::post('licenses', [LicenseController::class, 'store']);
    Route::post('licenses/verify', [LicenseController::class, 'verify']);

    Route::post('orders', [OrderController::class, 'store']);
    Route::get('product-plans', [ProductPlanController::class, 'index']);
    Route::post('product-plans', [ProductPlanController::class, 'store']);
    Route::post('payments/callbacks/{channel}', [PaymentCallbackController::class, 'store']);

    Route::get('ai/accounts/{tenant}', [AiUsageController::class, 'balance']);
    Route::post('ai/credits/grant', [AiUsageController::class, 'grant']);
    Route::post('ai/usage', [AiUsageController::class, 'store']);
    Route::post('ai/mock/completions', [AiUsageController::class, 'mockCompletion']);

    Route::post('plugins', [PluginController::class, 'store']);
    Route::post('plugins/{plugin}/releases', [PluginController::class, 'uploadRelease']);
    Route::post('plugins/install', [PluginController::class, 'install']);
    Route::post('plugins/download-tokens', [PluginController::class, 'issueDownloadToken']);
    Route::post('plugins/download-tokens/verify', [PluginController::class, 'verifyDownloadToken']);
    Route::post('plugins/updates/check', [PluginController::class, 'checkUpdate']);

    Route::post('workflows', [WorkflowController::class, 'store']);
    Route::post('workflows/run', [WorkflowController::class, 'run']);
    Route::post('workflows/runs/{run}/retry', [WorkflowController::class, 'retry']);

    Route::post('risk/blacklist', [RiskController::class, 'blacklist']);
    Route::post('risk/evaluate', [RiskController::class, 'evaluate']);
    Route::post('risk/rate-limit/check', [RiskController::class, 'rateLimit']);
    Route::post('risk/high-risk', [RiskController::class, 'highRisk']);

    Route::post('marketing/channels', [MarketingController::class, 'createChannel']);
    Route::post('marketing/promotion-links', [MarketingController::class, 'createPromotionLink']);
    Route::post('marketing/attributions', [MarketingController::class, 'attributePromotion']);
    Route::post('marketing/commissions/calculate', [MarketingController::class, 'calculateCommission']);
    Route::post('marketing/templates', [MarketingController::class, 'createTemplate']);
    Route::post('marketing/notifications/send', [MarketingController::class, 'sendNotification']);
    Route::post('marketing/renewals', [MarketingController::class, 'scheduleRenewal']);
    Route::post('marketing/renewals/process', [MarketingController::class, 'processRenewals']);
    Route::post('marketing/renewals/reminders/process', [MarketingController::class, 'processRenewalReminders']);
});
