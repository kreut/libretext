<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        /*curl -H  "Authorization:Bearer <token>" https://dev.adapt.libretexts.org/api/analytics -o analytics.zip
        Couldn't get this to work on staging (Internal Server error) so moved to dev*/

        if ($request->bearerToken() && $request->bearerToken() === config('myconfig.analytics_token')) {
            return Storage::disk('backup_s3')->get('analytics.zip');
        } else {
            return
                'Not authorized.';
        }

    }
}
