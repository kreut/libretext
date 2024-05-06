<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SketcherController extends Controller
{

    public function getSketcher(){
        return view('sketcher');
    }
}
