<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SetAppVersionHeader
{

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        //$app_version = env('VAPOR_COMMIT_HASH') ? env('VAPOR_COMMIT_HASH') : '1.0';
        if(!$response instanceof StreamedResponse) {
            //https://stackoverflow.com/questions/72060913/call-to-undefined-method-symfony-component-httpfoundation-streamedresponsehead
            $response->header('appversion', '2.31');
        }
        return $response;
    }
}
