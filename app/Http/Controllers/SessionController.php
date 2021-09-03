<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function set() {
        session()->put('test','value');
    }
    public function get() {
        var_dump(session()->get('test'));
    }
}
