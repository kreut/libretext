<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Http\Requests\StoreLTIPendingRegistrationRequest;
use App\LtiPendingRegistration;
use App\LtiRegistration;
use Exception;
use Illuminate\Support\Str;

class LTIPendingRegistrationController extends Controller
{
    /***
     * @param StoreLTIPendingRegistrationRequest $request
     * @return array
     * @throws Exception
     */
    public function store(StoreLTIPendingRegistrationRequest $request): array
    {
        try {
            $response['type'] = 'error';
            $data = $request->validated();
            $campus_id = Str::slug($data['campus']);
            if (LtiPendingRegistration::where('campus_id', $campus_id)->first()){
                $response['message'] = "$campus_id is already a pending LTI registration.";
                return $response;
            }
            if (LtiRegistration::where('campus_id', $campus_id)->first()){
                $response['message'] = "$campus_id is already a completed LTI registration.";
                return $response;
            }
            $ltiRegistration = new LtiPendingRegistration();
            $ltiRegistration->campus_id = $campus_id;
            $ltiRegistration->save();
            $response['type'] ='success';
            $response['campus_id'] = $campus_id;
            $response['message'] = "The campus ID has been created.";

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating the campus ID.  Please try again or contact us for assistance.";
        }
        return $response;
    }
}
