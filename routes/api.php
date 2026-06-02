<?php

use App\Http\Controllers\Api\V1\Analytics\AnalyticsController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Btc\BtcAssetController;
use App\Http\Controllers\Api\V1\Btc\BtcDashboardController;
use App\Http\Controllers\Api\V1\EmailSignupController;
use App\Http\Controllers\Api\V1\Finance\CreditCardController;
use App\Http\Controllers\Api\V1\Finance\CreditCardDebtController;
use App\Http\Controllers\Api\V1\Finance\FinanceSummaryController;
use App\Http\Controllers\Api\V1\Finance\LoanController;
use App\Http\Controllers\Api\V1\Finance\LoanInstallmentController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\Settings\SettingsController;
use App\Http\Controllers\Api\V1\Support\SupportTicketController;
use App\Http\Controllers\Api\V1\Support\SupportTicketMessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('health', HealthController::class);
    Route::post('email-signups', [EmailSignupController::class, 'store']);

    Route::prefix('auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::patch('me', [AuthController::class, 'updateMe']);

        Route::prefix('finance')->group(function (): void {
            Route::get('summary', FinanceSummaryController::class);
            Route::apiResource('credit-cards', CreditCardController::class);
            Route::apiResource('credit-card-debts', CreditCardDebtController::class);
            Route::apiResource('loans', LoanController::class);
            Route::get('loans/{loan}/installments', [LoanInstallmentController::class, 'index']);
            Route::patch('loan-installments/{loanInstallment}', [LoanInstallmentController::class, 'update']);
        });

        Route::prefix('btc')->group(function (): void {
            Route::get('dashboard', BtcDashboardController::class);
            Route::apiResource('assets', BtcAssetController::class)->parameters(['assets' => 'btcAsset']);
        });

        Route::get('analytics/overview', [AnalyticsController::class, 'overview']);

        Route::get('settings', [SettingsController::class, 'show']);
        Route::patch('settings', [SettingsController::class, 'update']);

        Route::prefix('support')->group(function (): void {
            Route::apiResource('tickets', SupportTicketController::class)->parameters(['tickets' => 'supportTicket']);
            Route::post('tickets/{supportTicket}/messages', [SupportTicketMessageController::class, 'store']);
        });
    });
});
