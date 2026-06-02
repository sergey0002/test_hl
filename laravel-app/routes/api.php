<?php

/**
 * API для задания 3: старт экспорта, статус, скачивание CSV.
 */

use App\Http\Controllers\Task3Controller;
use Illuminate\Support\Facades\Route;

Route::post('/export/start', [Task3Controller::class, 'startExport']);
Route::get('/export/status/{id}', [Task3Controller::class, 'getStatus']);
Route::get('/export/download/{id}', [Task3Controller::class, 'download']);
