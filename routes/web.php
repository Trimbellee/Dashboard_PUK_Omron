<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KodeSuratController;


/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/
Route::controller(AuthController::class)->group(function () {
    Route::get('/login', 'showLogin')->name('login');
    Route::post('/login', 'processLogin')->name('login.process');
    Route::get('/logout', 'logout')->name('logout');
});

/*
|--------------------------------------------------------------------------
| ROOT REDIRECT
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return session()->has('user')
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (middleware: authcheck)
|--------------------------------------------------------------------------
*/
Route::middleware(['authcheck'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | 💰 PENGAJUAN DANA
    |--------------------------------------------------------------------------
    */
    Route::prefix('pengajuan')->name('pengajuan.')->group(function () {
        Route::get('/', [PengajuanController::class, 'index'])->name('index');
        Route::get('/data', [PengajuanController::class, 'data'])->name('data');
        Route::post('/store', [PengajuanController::class, 'store'])->name('store');
        Route::post('/approve', [PengajuanController::class, 'approve'])->name('approve');
        Route::post('/update',[PengajuanController::class, 'update'])->name('update');
        Route::post('/reject', [PengajuanController::class, 'reject'])->name('reject');
        Route::post('/delete', [PengajuanController::class, 'delete'])->name('delete');
        Route::get('/export', [PengajuanController::class, 'export'])->name('export');
        
    });


    /*
    |--------------------------------------------------------------------------
    | 🧾 KODE PENOMORAN SURAT
    |--------------------------------------------------------------------------
    */
    Route::prefix('kode-surat')->name('kode-surat.')->group(function () {
        Route::get('/', [KodeSuratController::class, 'index'])->name('index');
        Route::post('/generate', [KodeSuratController::class, 'generate'])->name('generate');
        Route::post('/list', [KodeSuratController::class, 'list'])->name('list');
        Route::get('/data', [KodeSuratController::class, 'data'])->name('data');

    });

    /*
    |--------------------------------------------------------------------------
    | 👥 USER MANAGEMENT
    |--------------------------------------------------------------------------
    | Hanya bisa diakses oleh Ketua & Sekretaris
    |--------------------------------------------------------------------------
    */
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users/store', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/update/{username}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/delete/{username}', [UserController::class, 'destroy'])->name('users.destroy');
    

});
