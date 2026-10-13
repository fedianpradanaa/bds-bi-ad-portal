<?php

use App\Http\Controllers\Api\BdsReportUploadController;
use Illuminate\Support\Facades\Route;

Route::prefix('bds')->group(function () {
    Route::get('/test-connection', [BdsReportUploadController::class, 'testConnection'])->name('api.bds.test-connection');
    Route::post('/test-connection', [BdsReportUploadController::class, 'testConnection']);
    Route::get('/synced-state', [BdsReportUploadController::class, 'syncedState'])->name('api.bds.synced-state');
    Route::post('/upload-reports', [BdsReportUploadController::class, 'upload'])->name('api.bds.upload-reports');
});

Route::prefix('v1/bds')->group(function () {
    Route::get('/test-connection', [BdsReportUploadController::class, 'testConnection'])->name('api.v1.bds.test-connection');
    Route::post('/test-connection', [BdsReportUploadController::class, 'testConnection']);
    Route::get('/synced-state', [BdsReportUploadController::class, 'syncedState'])->name('api.v1.bds.synced-state');
    Route::post('/upload-reports', [BdsReportUploadController::class, 'upload'])->name('api.v1.bds.upload-reports');
});
