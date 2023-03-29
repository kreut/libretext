<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetAppVersionHeader
{

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        $response->header('appversion', '1.0');
        return $response;
    }
}
