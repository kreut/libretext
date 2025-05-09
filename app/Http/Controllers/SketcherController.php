<?php

namespace App\Http\Controllers;


class SketcherController extends Controller
{

    public function getSketcher($type = '')
    {
        switch ($type) {
            case('readonly'):
                return view('sketcher_readonly');
            case('empty_sketcher'):
                return view('empty_sketcher');
            default:
                return view('sketcher', ['configuration' => $type]);
        }

    }
}
