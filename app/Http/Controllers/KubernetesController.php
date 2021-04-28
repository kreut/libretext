<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class KubernetesController extends Controller
{
   public function metrics(){

       $response = '# HELP http_requests_total The amount of requests served by the server in total\n';
       $response .= '# TYPE http_requests_total counter\n';
       $response .= 'http_requests_total ' . config('myconfig.minpods') . '\n';
       return $response;

   }
}
