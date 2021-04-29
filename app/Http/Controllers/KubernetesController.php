<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class KubernetesController extends Controller
{
   public function metrics(){

       //TODO:  8:30-10:30am on Monday or 8:30-10:30 on Friday then return 10
       //$minpods = :30-10:30am on Monday or 8:30-10:30 on Friday then return 10 or config('myconfig.minpods')
       $minpods = config('myconfig.minpods');

       $response = "# HELP minpods Minimum number of pods required by the application\n";
       $response .= "# TYPE minpods gauge\n";
       $response .= "minpods " . $minpods. "\n";
       return response($response, 200)->header('Content-Type', 'text/plain');
   }
}
