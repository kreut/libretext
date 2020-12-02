<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GameController extends Controller
{
    public function game(Request $request){
require_once(resource_path('views') . '/web/game.php') ;

    }

    public function login(Request $request){
        require_once(resource_path('views') . '/web/login.php') ;

    }

    public function configure(Request $request, $launchId){
        $launch_id = $launchId;
        require_once(resource_path('views') . '/web/configure.php') ;

    }
}
