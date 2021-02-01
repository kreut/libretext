<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        'Illuminate\Auth\AuthenticationException',
        'Illuminate\Database\Eloquent\ModelNotFoundException'
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    protected function context()
    {
        return array_merge(parent::context(), [
            'request' => request()->all(),
            'controller' => request()->path()
        ]);
    }

    /**
     * Report or log an exception.
     *
     * @param \Throwable $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        $dontReport = [
            'Illuminate\Auth\AuthenticationException',
            'Illuminate\Database\Eloquent\ModelNotFoundException',
            'Illuminate\Validation\ValidationException'
        ];

        $logoutError = strpos(json_encode(request()->all()),'{"logoutRequest":"<samlp:LogoutRequest xmlns:samlp=\"urn:oasis:names:tc:SAML:2.0:protocol\"') !== false;

        if (!$logoutError && !in_array(get_class($exception),$dontReport)) {
            Log::error(sprintf(
                "Exception '%s'\r\n\tMessage: '%s'\r\n\tFile: %s:%d \r\n\tController: '%s' \r\n\tRequest: '%s'\r\n\tUser: '%s'",
                get_class($exception),
                $exception->getMessage(),
                $exception->getTrace()[0]['file'],
                $exception->getTrace()[0]['line'],
                request()->path(),
                json_encode(request()->all()),
                request()->user() ? request()->user()->id : 'No user logged in'
            ));
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Throwable $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Auth\AuthenticationException $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return $request->expectsJson()
            ? response()->json(['message' => $exception->getMessage()], 401)
            : redirect()->guest(url('/login'));
    }
}
