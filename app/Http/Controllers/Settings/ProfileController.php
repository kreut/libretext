<?php

namespace App\Http\Controllers\Settings;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile;
use \Exception;

class ProfileController extends Controller
{


    /**
     * @param Profile $request
     * @return mixed
     * @throws \Exception
     */
    public function update(Profile $request)
    {
        $user = $request->user();
        $response['type'] = 'error';
        try {
            $data = $request->validated();
            $user->update($data);
            $response['type'] = 'success';
            $response['message'] = 'Your profile has been updated.';
            $response['user'] = $user;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating your profile.  Please try again or contact us for assistance.";
        }
        return $response;
    }
}
