<?php

namespace App\Exceptions;

use Carbon\Carbon;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemException;
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
            'Overrides\IMSGlobal\LTI\LTI_Exception',
            'Illuminate\Http\Exceptions\ThrottleRequestsException'
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
            'api/oauth/libretexts/callback',
            'api/lti/get-token-by-lti-token-id'
        ]);
        $dontReportRequests = strpos($request, '{"0x":["androxgh0st"]}') !== false;
        $timeOnTaskError = $method === 'PATCH' && strpos($endpoint, 'api/assignment-question-time-on-tasks/assignment') !== false;
        $dontReports = $logoutError
            || $dontReportException
            || $dontReportFiles
            || $dontReportEndpoints
            || $dontReportRequests
            || $timeOnTaskError;

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
        switch ($exception->getMessage()) {
            case('Unauthenticated.'):
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
                break;
            case('Too Many Attempts.'):
                $data = [
                    'user_id' => request()->user() ? request()->user()->id : 'No user logged in',
                    'endpoint' => $endpoint,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                DB::table('throttles')->insert($data);
                break;
            default:
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
                    if (strpos($exception->getMessage(), 'submissions_user_id_assignment_id_question_id_unique') !== false) {
                        $log_file = "logs/assignment-question-id-unique-error.log";
                    } else {
                        $log_file = $dontReports ? "logs/unreported-errors.log" : "logs/laravel-$date.log";
                    }
                    try {
                        $contents = Storage::disk('s3')->exists("$log_file")
                            ? Storage::disk('s3')->get("$log_file") . "\r\n$error_info"
                            : $error_info;
                        Storage::disk('s3')->put("$log_file", $contents, ['StorageClass' => 'STANDARD_IA']);
                    } catch (Throwable $logException) {
                        // Never let a failed S3 write hide the original error:
                        // fall back to the default log (CloudWatch on Vapor).
                        Log::error($error_info);
                        Log::error('Could not write the error log to S3: ' . $logException->getMessage());
                    }
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
        // File storage (S3) failures: show a friendly message instead of a 500 page.
        // The error has already been reported above. Locally, keep the full error page for debugging.
        if ($exception instanceof FilesystemException && !app()->environment('local')) {
            $message = "We couldn't load that file right now. Please try again, or contact us if it keeps happening.";
            if ($request->is('discussion-comments/media-player/*')) {
                return response()->view('media_player_error', ['message' => $message], 503);
            }
            return $request->expectsJson()
                ? response()->json(['type' => 'error', 'message' => $message], 503)
                : response($message, 503);
        }

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
