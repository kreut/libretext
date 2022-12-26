<?php

namespace App\Exceptions;

use Carbon\Carbon;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Laravel\Facades\Telegram;
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
        $logoutError = strpos(json_encode(request()->all()), '{"logoutRequest":"<samlp:LogoutRequest xmlns:samlp=\"urn:oasis:names:tc:SAML:2.0:protocol\"') !== false;

        $dontReportException = in_array(get_class($exception), [
            'Illuminate\Auth\AuthenticationException',
            'Illuminate\Database\Eloquent\ModelNotFoundException',
            'Illuminate\Validation\ValidationException',
            'App\Exceptions\EmailTakenException',
            'Overrides\IMSGlobal\LTI\LTI_Exception'
        ]);
        $file = $exception->getTrace()[0]['file'] ?? 'None';
        $line = $exception->getTrace()[0]['line'] ?? 'None';
        $method = request()->method();
        $endpoint = request()->path();
        $request = json_encode(request()->all());
        $dontReportFiles = in_array($file, ['dns-query']);
        $dontReportEndpoints = in_array($endpoint, [
            'api/jsonws/invoke',
            'Autodiscover/Autodiscover.xml',
            'api/oauth/libretexts/callback'
        ]);
        $dontReportRequests = strpos($request, '{"0x":["androxgh0st"]}') !== false;
        $dontReports = $logoutError || $dontReportException || $dontReportFiles || $dontReportEndpoints || $dontReportRequests;

        $error_info = sprintf(
            "Exception '%s'\r\n\tMessage: '%s'\r\n\tFile: %s:%d \r\n\tMethod: '%s' \r\n\tEndpoint: '%s' \r\n\tRequest: '%s'\r\n\tUser: '%s'",
            get_class($exception),
            $exception->getMessage(),
            $file,
            $line,
            $method,
            $endpoint,
            $request,
            request()->user() ? request()->user()->id : 'No user logged in'
        );

        if (config('myconfig.loadtest')) {
            exit;
        }
        if ($exception->getMessage() === 'Unauthenticated.') {
            $data = [
                'file' => $file,
                'line' => $line,
                'method' => $method,
                'endpoint' => $endpoint,
                'request' => $request,
                'ip' => request()->ip(),
                'created_at' => now(),
                'updated_at' => now()];
            DB::table('no_user_logged_in_errors')->insert($data);
            if (app()->environment('local')) {
                Log::error($error_info);
            }
        } else {
            if (app()->environment('local')) {
                Log::error($error_info);
            } else if (app()->environment('testing')) {
                if (!$dontReports) {
                    Log::error($error_info);
                }
            } else {
                $date = Carbon::now('America/Los_Angeles')->format('Y-m-d');
                $date_time = Carbon::now('America/Los_Angeles');
                $error_info = "[$date_time] " . app()->environment() . "\r\n\tUrl: " . config('app.url') . "\r\n\tError: $error_info";
                $log_file = $dontReports ? "logs/unreported-errors.log" : "logs/laravel-$date.log";
                $contents = Storage::disk('s3')->exists("$log_file")
                    ? Storage::disk('s3')->get("$log_file") . "\r\n$error_info"
                    : $error_info;
                Storage::disk('s3')->put("$log_file", $contents, ['StorageClass' => 'STANDARD_IA']);
            }
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
