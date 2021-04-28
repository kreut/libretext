<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class KubernetesController extends Controller
{
   public function metrics(){

       //TODO:  8:30-10:30am on Monday or 8:30-10:30 on Friday then return 10
       //$minpods = :30-10:30am on Monday or 8:30-10:30 on Friday then return 10 or config('myconfig.minpods')
       $minpods = 0;
       $response = '# HELP http_requests_total The amount of requests served by the server in total\n';
       $response .= '# TYPE http_requests_total counter\n';
       $response .= 'http_requests_total ' . $minpods. '\n';
       return $response;

   }
}
