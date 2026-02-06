<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/invoice', [InvoiceController::class, 'index']);
Route::post('/invoice/print', [InvoiceController::class, 'print']);
Route::get('/upload-nv-logo', [InvoiceController::class, 'uploadNvLogo']);

