<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class KubernetesController extends Controller
{
    public function metrics()
    {

        $today = Carbon::today('America/Los_Angeles')->toDateString();
        $minpods = (int) config('myconfig.minpods');
        if ($minpods === 0 && in_array($today, ['2021-05-03', '2021-05-07'])){
            $minpods = 10;
        }

        $response = "# HELP minpods Minimum number of pods required by the application\n";
        $response .= "# TYPE minpods gauge\n";
        $response .= "minpods " . $minpods . "\n";
        return response($response, 200)->header('Content-Type', 'text/plain');
    }
}
