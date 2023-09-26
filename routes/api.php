<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\GoodsController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\InstallmentTransactionController;
use App\Http\Controllers\ReportController;

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

});

Route::controller(TransactionController::class)->group(function () {
    Route::get('/transaction/', 'index');
    Route::get('/transaction/{id}', 'show'); 
    Route::post('/transaction', 'store');
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

Route::controller(ReportController::class)->group(function () {
    Route::post('/report', 'index');
});