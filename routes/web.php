<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductExportController;
use App\Http\Controllers\ProductTransactionController;
use App\Http\Controllers\TransactionExportController;

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

Route::get('/', function () {
    return view('welcome');
});
Route::get('/export-products-pdf', [ProductExportController::class, 'pdf']);
Route::get('/export-transactions-pdf/{type}', [TransactionExportController::class, 'pdf']);
Route::get('/export-product-transactions-pdf/{kodeBarang}', [ProductTransactionController::class, 'pdf']);
