<?php

namespace App\Exceptions;

use Throwable; // Tambahkan ini
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    public function render($request, Throwable $exception): Response
    {
        if ($exception instanceof QueryException) {
            return response()->json([
                'status' => 500,
                'message' => 'Database connection failed',
                'data' => null,
            ], 500);
        }
    
        return response()->json([
            'status' => 500,
            'message' => 'An unexpected error occurred',
            'data' => null,
        ], 500);
    }
}
