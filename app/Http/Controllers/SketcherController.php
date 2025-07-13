<?php

namespace App\Http\Controllers;


class SketcherController extends Controller
{

    public function getSketcher($type = '')
    {
        switch ($type) {
            case('readonly'):
                $view = view('sketcher_readonly');
                break;
            case('empty_sketcher'):
                $view = view('empty_sketcher');
                break;
            default:
                $view = view('sketcher', ['configuration' => $type]);
                break;
        }
        return response($view)
            ->header('X-Debug-CSP', 'yes')
            ->header('Content-Security-Policy',
                "default-src 'self'; " .
                "script-src 'self' https://cdnjs.cloudflare.com https://molview.libretexts.org 'unsafe-inline'; " .
                "style-src 'self' 'unsafe-inline'; " .
                "frame-src 'self' https://molview.libretexts.org; " .
                "connect-src 'self' https://cactus.nci.nih.gov; " .
                "frame-ancestors *;"
            );

    }
}
