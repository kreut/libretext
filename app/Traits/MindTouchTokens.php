<?php


namespace App\Traits;


use GuzzleHttp\Client;

trait MindTouchTokens

{
    public function getTokens()
    {
        $client = new Client();
        $response = $client->get('https://cdn.libretexts.net/authenBrowser.json');
        return json_decode($response->getBody());


    }

}
