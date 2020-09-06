<?php


namespace App\Traits;


use GuzzleHttp\Client;

trait MindTouchTokens

{
    public function getTokens()
    {
        $client = new Client();
        $response = $client->get('https://files.libretexts.org/authenBrowser.json');
        return json_decode($response->getBody());


    }

}
