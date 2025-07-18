<?php

use App\Http\Controllers\AggregatorController;
use App\Http\Controllers\AggregatorDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CronContoller;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'loginPost']);

Route::get('/updatetrx', [CronContoller::class, 'changeStatus'])->name('status');


// ONLY AUTHENTICATED USERS CAN ACCESS THE FOLLOWING ROUTES
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/update/callback', [AggregatorController::class, 'updateCallback'])->name('updateCallback');

    Route::middleware(['admin'])->group(function () {

        /******MERCHANT */
        Route::resource('merchant', MerchantController::class);
        Route::post('merchant/assign-aggregator', [MerchantController::class, 'assign'])->name('merchant.assign');
        Route::post('merchant/unassign-aggregator/{id}', [MerchantController::class, 'unassign'])->name('merchant.unassign');
        /******END OF MERCHANT */

        Route::resource('aggregator', AggregatorController::class);

        /*******TRANSACTION RESOURCE */
        Route::resource('transaction', TransactionController::class);
    });
});

// WEBHOOKS
Route::post('/webhook/event', [WebhookController::class, 'event']);