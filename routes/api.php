<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\GoodsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\InstallmentTransactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\BuildingInstallmentController;
use App\Http\Controllers\BuildingRefundController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\BuildingNoteController;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
    Route::post('change-password', 'changePassword');
    
    Route::get('me', 'me');


});

Route::controller(UserController::class)->group(function () {
    Route::get('/users', 'index');
    Route::get('/users/{id}', 'show');
    Route::delete('/users/{id}', 'destroy');

});

Route::controller(ProjectController::class)->group(function () {
    Route::get('/project', 'index');
    Route::get('/project/{id}', 'show');
    Route::post('/project', 'store');
    Route::put('/project/{id}', 'update');
    Route::delete('/project/{id}', 'destroy');

});

Route::controller(SupplierController::class)->group(function () {
    Route::get('/supplier', 'index');
    Route::post('/supplier', 'store');
    Route::get('/supplier/{id}', 'show');
    Route::put('/supplier/{id}', 'update');
    Route::delete('/supplier/{id}', 'destroy');

});

Route::controller(GoodsController::class)->group(function () {
    Route::get('/goods', 'index');
    Route::get('/goods/{id}', 'show');
    Route::post('/goods', 'store');
    Route::put('/goods/{id}', 'update');
    Route::delete('/goods/{id}', 'destroy');
    Route::get('/goods/log/{id}', 'log');


});

Route::controller(TransactionController::class)->group(function () {
    Route::get('/transaction/', 'index');
    Route::get('/transaction/{id}', 'show'); 
    Route::post('/transaction/', 'store');
    Route::put('/transaction/{id}', 'update');
    Route::delete('/transaction/{id}', 'destroy');
});

Route::controller(InstallmentTransactionController::class)->group(function () {
    Route::get('/installment', 'index');
    Route::get('/installment/{id}', 'show'); 
    Route::post('/installment', 'store');
    Route::put('/installment/{id}', 'update');
    Route::delete('/installment/{id}', 'destroy');
});

Route::controller(NoteController::class)->group(function () {
    Route::get('/note', 'index');
    Route::get('/note/{id}', 'show'); 
    Route::post('/note', 'store');
    Route::put('/note/{id}', 'update');
    Route::delete('/note/{id}', 'destroy');
});

Route::controller(BuildingController::class)->group(function () {
    Route::get('/building', 'index');
    Route::get('/building/{id}', 'show'); 
    Route::post('/building', 'store');
    Route::put('/building/{id}', 'update');
    Route::delete('/building/{id}', 'destroy');
});

Route::controller(BuildingInstallmentController::class)->group(function () {
    Route::get('/building-installment', 'index');
    Route::get('/building-installment/{id}', 'show'); 
    Route::post('/building-installment', 'store');
    Route::put('/building-installment/{id}', 'update');
    Route::delete('/building-installment/{id}', 'destroy');
});


Route::controller(BuildingRefundController::class)->group(function () {
    Route::get('/building-refund', 'index');
    Route::get('/building-refund/{id}', 'show'); 
    Route::post('/building-refund', 'store');
    Route::put('/building-refund/{id}', 'update');
    Route::delete('/building-refund/{id}', 'destroy');
});

Route::controller(ExpenseController::class)->group(function () {
    Route::get('/expenses', 'index');
    Route::get('/expenses/{id}', 'show'); 
    Route::post('/expenses', 'store');
    Route::put('/expenses/{id}', 'update');
    Route::delete('/expenses/{id}', 'destroy');
});

Route::controller(BuildingNoteController::class)->group(function () {
    Route::get('/buildingnote', 'index');
    Route::get('/buildingnote/{id}', 'show'); 
    Route::post('/buildingnote', 'store');
    Route::put('/buildingnote/{id}', 'update');
    Route::delete('/buildingnote/{id}', 'destroy');
});

Route::controller(PermissionController::class)->group(function () {
    Route::get('/permissions', 'index');
    Route::put('/permissions/{rolename}', 'update');
});

Route::controller(ReportController::class)->group(function () {
    Route::post('/report', 'index');
    Route::post('/cashflow', 'caseFlow');
    Route::get('/penjualanrumah', 'penjualanRumah');
    Route::get('/penjualanrumahexcel', 'penjualanRumahExcel');
    Route::get('/tes', 'paidData');
    Route::get('/building-report', 'buildingReport');
    Route::get('/kas', 'kasExpenses');
    Route::get('/t', 'installmentUntilMonth');
    Route::get('/debt-tracker', 'debtTracker');
    Route::post('/restore', 'restore');

});