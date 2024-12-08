<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [];

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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
{
    if ($request->is('api/*')) {
        // صيغة الخطأ المخصصة لواجهات API
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
        ], $this->getStatusCode($exception));
    }

    return parent::render($request, $exception);
}

/**
 * تحديد كود الحالة المناسب لكل استثناء.
 */
protected function getStatusCode(Throwable $exception): int
{
    if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
        return 404;
    }

    if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
        return 401;
    }

    if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
        return 404;
    }

    return 500; // حالة الخطأ العام
}


protected function unauthenticated($request, AuthenticationException $exception)
{
    if ($request->expectsJson()) {
        return response()->json([
            'success' => false,
            'message' => 'Authentication required. Please log in.',
        ], 401);
    }

    return redirect()->guest(route('login'));
}

}
