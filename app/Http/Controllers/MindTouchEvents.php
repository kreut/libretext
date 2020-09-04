<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MindTouchEvents extends Controller
{
    public function update(Request $request) {
        file_put_contents('test.txt', 'made it');
    }
}
