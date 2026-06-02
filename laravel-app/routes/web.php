<?php

/**
 * Web-маршруты UI тестового задания.
 */

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Task1Controller;
use App\Http\Controllers\Task2Controller;
use App\Http\Controllers\Task3Controller;
use Illuminate\Support\Facades\Route;

// Стартовая страница с контактами.
Route::get('/', [HomeController::class, 'index'])->name('home');

// Задание 1: форма сортировки (GET — демо-данные, POST — запуск).
Route::get('/task1', [Task1Controller::class, 'index'])->name('task1.index');
Route::post('/task1', [Task1Controller::class, 'sort'])->name('task1.sort');

Route::get('/task2', [Task2Controller::class, 'index'])->name('task2.index');
Route::get('/task2/dump', [Task2Controller::class, 'downloadDump'])->name('task2.dump');
Route::post('/task2/query', [Task2Controller::class, 'runQuery'])->name('task2.query');

Route::get('/export', [Task3Controller::class, 'index'])->name('task3.index');
