<?php

namespace App\Exceptions;

use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->renderable(function (PostTooLargeException $e, $request) {
            if ($request->expectsJson() || $request->ajax() || $request->routeIs('biodata.upload.document')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ukuran file melebihi batas upload server. Maksimal 2 MB per dokumen, kecuali Sertifikat Pendukung maksimal 50 MB.',
                ], 413);
            }
        });

        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
