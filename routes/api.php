<?php

use App\Http\Controllers\GaReportController;
use Illuminate\Support\Facades\Route;


Route::get('/report/realtime', [GaReportController::class, 'runTimeReport'])->name('report.realtime');
Route::get('/report', [GaReportController::class, 'report'])->name('report');
