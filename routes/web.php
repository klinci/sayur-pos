<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use Illuminate\Http\Request;

$basicAuthMiddleware = function (Request $request, $next) {
    $user = $request->getUser();
    $password = $request->getPassword();

    if ($user === env('BASIC_AUTH_USER', 'admin') && $password === env('BASIC_AUTH_PASSWORD', 'rahasia123')) {
        return $next($request);
    }

    return response('Akses Ditolak.', 401, [
        'WWW-Authenticate' => 'Basic realm="Halaman Terproteksi"'
    ]);
};


Route::group(['middleware' => [$basicAuthMiddleware]], function () {

        Route::get('/', function () {
            return redirect('/invoice');
        });

        Route::get('/invoice', [InvoiceController::class, 'index'])->name('invoice.index');
        Route::post('/invoice/print', [InvoiceController::class, 'print'])->name('invoice.print');
        Route::get('/upload-nv-logo', [InvoiceController::class, 'uploadNvLogo']);

        // Routes Master Data
        Route::get('/master-data', [InvoiceController::class, 'masterData'])->name('product.master');
        Route::post('/master-data', [InvoiceController::class, 'storeProduct'])->name('product.store');
        Route::delete('/master-data/{id}', [InvoiceController::class, 'deleteProduct'])->name('product.delete');
        Route::put('/master-data/{id}', [InvoiceController::class, 'updateProduct'])->name('product.update');

        Route::get('/history', [InvoiceController::class, 'history'])->name('history');
        Route::get('/history/{id}/edit', [InvoiceController::class, 'editHistory'])->name('history.edit');
        Route::put('/history/{id}', [InvoiceController::class, 'updateHistory'])->name('history.update');
        Route::get('/history/reprint/{id}', [InvoiceController::class, 'reprint'])->name('history.reprint');// Pastikan ada route ini
        Route::delete('/history/{id}', [InvoiceController::class, 'deleteTransaction'])->name('history.delete');

        Route::get('/history/pdf/{id}', [InvoiceController::class, 'generatePDF'])->name('history.pdf');
        Route::get('/report/summary', [InvoiceController::class, 'reportSummary'])->name('report.summary');
        Route::get('/report/charts', [InvoiceController::class, 'reportCharts'])->name('report.charts');

});

