<?php

namespace App\Http\Controllers\Settings;

use App\Exceptions\Handler;
use \Exception;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    /**
     * @param Request $request
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'password' => 'required|confirmed|min:6',
        ]);
        $response['type'] = 'error';
        try {
            $request->user()->update([
                'password' => bcrypt($request->password),
            ]);
            $response['type'] = 'success';
            $response['message'] = 'Your password has been updated.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating your password.  Please try again by refreshing the page or contact us for assistance.";
        }
        return $response;
    }
}
