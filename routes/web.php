<?php

use App\Http\Controllers\ShipmentReportPrintController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/projects/{project}/shipments/print', ShipmentReportPrintController::class)
        ->name('shipment-report.print');
});
