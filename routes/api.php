<?php

use App\Http\Controllers\Api\Internal\CandidateDocumentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('internal')->name('internal.')->group(function () {
    Route::post('candidate-documents', [CandidateDocumentController::class, 'index'])
        ->middleware('internal.api')
        ->name('candidate-documents.index');

    Route::get('candidate-documents/{no_ktp}/{type}/preview', [CandidateDocumentController::class, 'preview'])
        ->middleware('signed')
        ->where('type', '[A-Za-z0-9_]+')
        ->name('candidate-documents.preview');

    Route::get('candidate-documents/{no_ktp}/{type}/download', [CandidateDocumentController::class, 'download'])
        ->middleware('signed')
        ->where('type', '[A-Za-z0-9_]+')
        ->name('candidate-documents.download');
});
