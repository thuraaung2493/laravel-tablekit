<?php

use Illuminate\Support\Facades\Route;
use TableKitDemo\DataTables\ClientTable;
use TableKitDemo\Http\Controllers\ClientController;
use TableKitDemo\Http\Controllers\SessionController;

Route::get('/login', [SessionController::class, 'create'])->name('login');
Route::post('/login', [SessionController::class, 'store']);
Route::middleware('auth')->group(function () {
    Route::get('/', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/data', ClientTable::class)->name('clients.data');
    Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::post('/clients/bulk', [ClientController::class, 'bulk'])->name('clients.bulk');
    Route::get('/clients/export', [ClientController::class, 'export'])->name('clients.export');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
});
