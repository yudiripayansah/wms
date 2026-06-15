<?php

use App\Exports\TransactionImportTemplateExport;
use App\Http\Controllers\AllocationPreviewController;
use App\Http\Controllers\InventoryExportController;
use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\TransactionExportController;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'id'])) {
        session(['locale' => $locale]);
        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }
    }
    return redirect()->back();
})->name('locale.switch');

Route::get('/export-inventories-pdf', [InventoryExportController::class, 'pdf']);
Route::get('/export-transactions-pdf/{type}', [TransactionExportController::class, 'pdf']);
Route::get('/export-inventory-transactions-pdf/{barcode}', [InventoryTransactionController::class, 'pdf']);

Route::middleware(['auth'])->group(function () {
    Route::get('/templates/transaction-import', function () {
        return Excel::download(new TransactionImportTemplateExport(), 'template-transaction-import.xlsx');
    })->name('template.transaction-import');

    Route::get('/allocation-preview/{allocation}/{form?}', [AllocationPreviewController::class, 'preview'])
        ->name('allocation.preview')
        ->where('form', 'location|barcode');

    Route::get('/allocation-pdf/{allocation}/{form?}', [AllocationPreviewController::class, 'pdf'])
        ->name('allocation.pdf')
        ->where('form', 'location|barcode');
});
