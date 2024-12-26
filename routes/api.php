<?php

use App\Http\Controllers\GaReportController;
use Illuminate\Support\Facades\Route;


Route::get('/report/runtime', [GaReportController::class, 'runtime'])->name('report.runtime');
Route::get('/report', [GaReportController::class, 'report'])->name('report');
