<?php

use App\Http\Controllers\BdsCertificatePrintController;
use App\Http\Controllers\ExternalApprovalController;
use App\Http\Controllers\OutputReportPrintController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::prefix('approval')->middleware(['throttle:60,1'])->group(function () {
    Route::get('/{token}', [ExternalApprovalController::class, 'show'])->name('approval.show');
    Route::post('/{token}/approve', [ExternalApprovalController::class, 'approve'])->name('approval.approve');
    Route::post('/{token}/reject', [ExternalApprovalController::class, 'reject'])->name('approval.reject');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/bds-certificate-print', BdsCertificatePrintController::class)
        ->name('bds.certificate.print');
    Route::get('/admin/bds-output-report-print', OutputReportPrintController::class)
        ->name('bds.output-report.print');
});
