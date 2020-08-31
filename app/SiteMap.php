<?php

namespace App;

use App\Question;
use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Client;

use Illuminate\Support\Facades\DB;

use App\Exceptions\Handler;
use \Exception;

class SiteMap extends Model
{
    protected $tags;
    protected $questionIds;
    protected $technologyIds;
    protected $client;
    protected $tokens;

    public function __construct(array $attributes = [])
    {
        //parent::__construct($attributes);

        $this->client = new Client();
        $this->tokens = $this->getTokens();

    }

    public function init()
    {

            $sitemaps = $this->getSiteMaps();
            foreach ($sitemaps as $sitemap) {
                set_time_limit(0);
                echo $sitemap . "\r\n";
                $this->iterateSiteMap($sitemap);
            }
    }

    public function getTokens()
    {
        $response = $this->client->get('https://files.libretexts.org/authenBrowser.json');
        return json_decode($response->getBody());


    }

    public function isValidAssessment($loc)
    {
        $validPaths = ['https://query.libretexts.org/Assessment_Gallery/H5P_Assessments/',
            'https://query.libretexts.org/Assessment_Gallery/IMathAS_Assessments/',
            'https://query.libretexts.org/Assessment_Gallery/WeBWorK_Assessments/',
            'https://query.libretexts.org?title=Assessment_Gallery/'];

        foreach ($validPaths as $path)
            if (strpos($loc, $path) === 0) {
                return true;
            }
        return false;
    }

    public function iterateSiteMap($sitemap)
    {
        $response = $this->client->get($sitemap);
        $xml = simplexml_load_string($response->getBody());

        foreach ($xml->url as $value) {

            $loc = $value->loc[0];
            if ($this->isValidAssessment($loc)) {
                $this->getLocInfo($loc);
                usleep(500000);
                file_put_contents('questions.txt', "$loc \r\n", FILE_APPEND);
            }
        }
    }

    public function getLocInfo($loc)

    {
        try {
        $host = parse_url($loc)['host'];
        $path = substr(parse_url($loc)['path'], 1);//get rid of trailing slash

        $library = str_replace('.libretexts.org', '', $host);
        $tokens = $this->tokens;
        $token = $tokens->{$library};
        $headers = ['Origin' => 'https://adapt.libretexts.org', 'x-deki-token' => $token];

        $final_url = "https://$library.libretexts.org/@api/deki/pages/=" . urlencode($path) . '?dream.out.format=json';

            $response = $this->client->get($final_url, ['headers' => $headers]);
            $page_info = json_decode($response->getBody(), true);

            $page_id = $page_info['@id'];
            //file_put_contents('sitemap', "$final_url $page_id \r\n", FILE_APPEND);
            $tags = [];
            if ($page_info['tags']['tag']) {
                foreach ($page_info['tags']['tag'] as $key => $value) {
                    $tag = $value['@value'];
                    if (strpos($tag, 'tech:') === 0) {
                        $technology = str_replace('tech:', '', $tag);
                    } else {
                        $tags[] = strtolower($tag);
                    }
                }
            }

            $question = Question::firstOrCreate(['page_id' => $page_id,
                'technology' => $technology,
                'location' =>  $loc]);
            $Question = new Question;

            if ($tags) {
                foreach ($tags as $key => $tag) {
                    $Question->addTag($tag, mb_strtolower($tag), $question);
                }
            }

        } catch (Exception $e) {
            file_put_contents('site_map_errors', $e->getMessage() . ":  $loc \r\n", FILE_APPEND);
        }

    }

    public
    function getSiteMaps()
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
