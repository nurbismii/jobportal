<?php

use App\Http\Controllers\Admin\LamaranController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\BiodataController;
use App\Http\Controllers\PendaftaranController;
use App\Http\Controllers\ResetPasswordController;
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
Route::get('/', [App\Http\Controllers\BerandaController::class, 'index'])->name('beranda');

Route::resource('lowongan-kerja', 'App\Http\Controllers\LowonganController');
Route::resource('pengumuman', 'App\Http\Controllers\PengumumanController');
Route::resource('bantuan', 'App\Http\Controllers\BantuanController');
Route::resource('pendaftaran', 'App\Http\Controllers\PendaftaranController');

Route::get('konfirmasi-email-token/{token}', [PendaftaranController::class, 'konfirmasiEmailToken']);

Route::resource('reset-password', 'App\Http\Controllers\ResetPasswordController');
Route::get('reset-password-token/{token}', [ResetPasswordController::class, 'resetPassword']);

// User harus login dan sudah verifikasi email untuk akses biodata dan profil
Route::middleware(['verified.email'])->group(function () {

    Route::resource('lamaran', 'App\Http\Controllers\LamaranController');
    Route::resource('profil', 'App\Http\Controllers\ProfilController');

    Route::resource('biodata', 'App\Http\Controllers\BiodataController');
    Route::delete('/biodata/delete-file/{field}', [BiodataController::class, 'deleteFile'])->name('biodata.deleteFile');
});

Auth::routes();

// Admin route
Route::group(['prefix' => 'admin', 'middleware' => ['redirect.role']], function () {

    Route::get('/', [App\Http\Controllers\Admin\DasborController::class, 'index']);
    Route::get('/dasbor', [App\Http\Controllers\Admin\DasborController::class, 'index'])->name('home');
    Route::get('/dasbor/lowongan-data', [App\Http\Controllers\Admin\DasborController::class, 'lowonganData']);
    Route::get('/dasbor/lowongan-chart', [App\Http\Controllers\Admin\DasborController::class, 'lowonganChart']);

    Route::resource('/pengguna', 'App\Http\Controllers\Admin\PenggunaController');

    Route::resource('/lowongan', 'App\Http\Controllers\Admin\LowonganController');
    Route::get('/lowongan/pendaftar/{loker_id}', [App\Http\Controllers\Admin\LowonganController::class, 'directToLamaran'])->name('directToLamaran');

    Route::resource('/lamarans', 'App\Http\Controllers\Admin\LamaranController');
    Route::post('/lamaran/update-status-massal', [LamaranController::class, 'updateStatusMassal'])->name('lamaran.updateStatusMassal');
    Route::get('/lamaran-data', [LamaranController::class, 'getLamaranData'])->name('lamaran.data');

    Route::resource('/pengumumans', 'App\Http\Controllers\Admin\PengumumanController');

    Route::post('/auto-update-field', [LamaranController::class, 'autoUpdate'])->name('data.autoUpdate');

    Route::resource('/personal-file', 'App\Http\Controllers\Admin\PersonalController');
    Route::resource('/permintaan-tenaga-kerja', 'App\Http\Controllers\Admin\PermintaanTenagaKerjaController');
});

// API route
Route::group(['prefix' => 'api'], function () {
    Route::get('kabupaten/{id}', [App\Http\Controllers\ApiController::class, 'fetchKabupaten']);
    Route::get('kecamatan/{id}', [App\Http\Controllers\ApiController::class, 'fetchKecamatan']);
    Route::get('kelurahan/{id}', [App\Http\Controllers\ApiController::class, 'fetchKelurahan']);

    Route::get('/get-divisi/{departemen_id}', [App\Http\Controllers\ApiController::class, 'getByDepartemen']);
    Route::get('/lowongan-by-ptk/{ptk_id?}', [App\Http\Controllers\ApiController::class, 'getLowongan']);

    Route::post('/biodata/ocr/sim-b2', [App\Http\Controllers\ApiController::class, 'ocrSimB2'])->name('biodata.ocr.sim_b2');
    Route::post('/biodata/ocr/ktp', [App\Http\Controllers\ApiController::class, 'ocrKtp'])->name('biodata.ocr.ktp');
});
