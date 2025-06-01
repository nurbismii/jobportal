<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// User route
Route::get('/', function () {
    return view('beranda');
});

Route::resource('lowongan-kerja', 'App\Http\Controllers\LowonganController');
Route::resource('pengumuman', 'App\Http\Controllers\PengumumanController');
Route::resource('bantuan', 'App\Http\Controllers\BantuanController');
Route::resource('pendaftaran', 'App\Http\Controllers\PendaftaranController');

Auth::routes();

Route::resource('biodata', 'App\Http\Controllers\BiodataController');

Route::group(['prefix' => 'api'], function () {
    Route::get('kabupaten/{id}', [App\Http\Controllers\ApiController::class, 'fetchKabupaten']);
    Route::get('kecamatan/{id}', [App\Http\Controllers\ApiController::class, 'fetchKecamatan']);
    Route::get('kelurahan/{id}', [App\Http\Controllers\ApiController::class, 'fetchKelurahan']);
});

// Admin route
Route::group(['prefix' => 'admin', 'middleware' => ['redirect.role']], function () {

    Route::get('/dasbor', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::resource('/pengguna', 'App\Http\Controllers\Admin\PenggunaController');
    Route::resource('/lowongan', 'App\Http\Controllers\Admin\LowonganController');
});
