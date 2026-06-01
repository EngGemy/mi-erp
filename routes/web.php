<?php

use App\Http\Controllers\ShipmentReportPrintController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/projects/{project}/shipments/print', ShipmentReportPrintController::class)
        ->name('shipment-report.print');

    // Logos uploaded before FileUpload::disk('public') — served from private disk (admin only).
    Route::get('/crown/legacy-storage/{path}', function (string $path) {
        $path = str_replace(['..', '\\'], '', $path);
        if (! preg_match('#^settings/logo/.+#', $path)) {
            abort(404);
        }
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    })->where('path', '.*')->name('crown.private-file');
});
