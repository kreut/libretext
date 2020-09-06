<?php


namespace App\Traits;


trait MindTouchTokens

{
    public function getTokens()
    {
        $response = $this->client->get('https://files.libretexts.org/authenBrowser.json');
        return json_decode($response->getBody());


    }

}
