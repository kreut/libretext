<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use Exception;


class AccessibilityController extends Controller
{
    public function setCookie(){
        try {
            $cookie = cookie()->forever('accessibility', 1, null, null, null, false, false,'none');
            $response['type'] = 'success';
            $response['message'] = 'We have added accessibility features which will be implemented in this browser.';
        } catch (Exception $e){
            $response['message'] = 'There was an error setting the accessibility cookie.';
            $h = new Handler(app());
            $h->report($e);
            return $response;
        }

        return response($response)->withCookie($cookie);

    }

    public function destroyCookie(){
        try {
            $cookie = cookie()->forget('accessibility');
            $response['type'] = 'success';
            $response['message'] = 'We have removed the accessibility features.';
        } catch (Exception $e){
            $response['message'] = 'There was an error removing the accessibility cookie.';
            $h = new Handler(app());
            $h->report($e);
            return $response;
        }

        return response($response)->withCookie($cookie);

    }
}
