<?php

namespace App\Http\Controllers;

use App\CannedResponse;
use App\Exceptions\Handler;
use App\Http\Requests\StoreCannedResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use SebastianBergmann\ObjectReflector\Exception;

class CannedResponseController extends Controller
{
    public function index(Request $request, CannedResponse $cannedResponse)
    {


        $response['type'] = 'error';
        $authorized = Gate::inspect('index', $cannedResponse);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $canned_responses_info = $cannedResponse->where('user_id', $request->user()->id)->get();
            $canned_responses = [];
            foreach ($canned_responses_info as $canned_response) {
                $canned_responses[] = ['id' => $canned_response->id,
                    'canned_response'=>$canned_response->canned_response];
            }
            $response['canned_responses'] = $canned_responses;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve the file submissions for this assignment.  Please try again or contact us for assistance.";
        }

        return $response;
    }

    public function store(StoreCannedResponse $request, CannedResponse $cannedResponse)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('store', $cannedResponse);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $data = $request->validated();
            $cannedResponse->canned_response = $data['canned_response'];
            $cannedResponse->user_id = $request->user()->id;
            $cannedResponse->save();
            $response['type'] = 'success';
            $response['message'] = 'Your canned response has been saved.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your canned response.  Please try again or contact us for assistance.";
        }

        return $response;
    }

    public function destroy(Request $request, CannedResponse $cannedResponse)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('destroy', $cannedResponse);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $cannedResponse->delete();
            $response['type'] = 'info';
            $response['message'] = "Your canned response has been removed.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to remove your canned response.  Please try again or contact us for assistance.";
        }

        return $response;
    }
}
