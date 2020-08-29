<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Client;

class SiteMap extends Model
{

    public function init()
    {
        $this->client = new Client();;
        $this->tokens = $this->getTokens();
        $sitemaps = $this->getSiteMaps();
        foreach ($sitemaps as $sitemap) {
            echo $sitemap . "\r\n";
            $this->iterateSiteMap($sitemap);
            exit;
        }

    }

    public function getTokens()
    {
        $response = $this->client->get('https://files.libretexts.org/authenBrowser.json');
        return json_decode($response->getBody());


    }

    public function iterateSiteMap($sitemap)
    {
        $response = $this->client->get($sitemap);
        $xml = simplexml_load_string($response->getBody());
        $key = 0;
        $urls = [];
        foreach ($xml->url as $value) {
            $loc = (string)$value->loc[0];
            if (strpos($loc, 'Assessment_Gallery') !== false) {
                $urls[$key] = (string)$value->loc[0];
                $loc_info = $this->getLocInfo($urls[$key]);
                print_r($loc_info);
                $key++;
                if ($key > 0) {
                    break;
                }
            }
        }
    }

    public function getLocInfo($url)
    {

        $host = parse_url($url)['host'];
        $path = substr(parse_url($url)['path'],1);

        $library = str_replace('.libretexts.org', '', $host);
        $tokens = $this->tokens;
        $token = $tokens->{$library};
        $headers = ['Origin' => 'dev.adapt.libretexts.org', 'x-deki-token' => $token];

        $final_url = "https://$library.libretexts.org/@api/deki/pages/=" . urlencode($path);

        try {
            $response = $this->client->get($final_url, ['debug'=>true, 'headers' => $headers]);
            $xml = simplexml_load_string($response->getBody());
            var_dump($xml);
        } catch (Exception $e) {
            echo $e->getMessage();
        }


    }

    public function getSiteMaps()
    {

        $response = $this->client->get('https://query.libretexts.org/sitemap.xml');
        $xml = simplexml_load_string($response->getBody());
        $key = 0;
        $sitemaps = [];
        foreach ($xml->sitemap as $value) {
            $sitemaps[$key] = (string)$xml->sitemap[$key]->loc[0];
            $key++;
        }
        return $sitemaps;
    }


}
