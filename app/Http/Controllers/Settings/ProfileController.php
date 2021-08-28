<?php

namespace App\Http\Controllers\Settings;

use App\Exceptions\Handler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile;
use \Exception;
use Illuminate\Support\Facades\Gate;

class ProfileController extends Controller
{


    /**
     * @param Profile $request
     * @return array
     * @throws Exception
     */
    public function update(Profile $request): array
    {
        $user = $request->user();
        $response['type'] = 'error';
        $profileModel = new \App\Profile();
        $authorized = Gate::inspect('update',$profileModel);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
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
