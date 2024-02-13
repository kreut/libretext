<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use Exception;
use Illuminate\Http\Request;
use phpcent\Client;

class CentrifugoController extends Controller
{
    /**
     * @param Request $request
     * @return array
     * @throws Exception'
     */
    public function token(Request $request): array
    {
        $response['type'] = 'error';
        try {
            $client = new Client(Helper::centrifugeUrl());
            $token = $client->setSecret(config('myconfig.centrifugo_secret_key'))->generateConnectionToken($request->user()->id);
            $response['token'] = $token;
            $response['domain'] = config('myconfig.centrifugo_domain');
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get a list of your saved folders.  Please try again or contact us for assistance.";
        }
        return $response;
    }
}
