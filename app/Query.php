<?php

namespace App;

use App\MindTouchEvent;
use App\Question;
use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Exceptions\Handler;
use \Exception;

class Query extends Model
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

    public function import()
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
                $used_api_to_get_tags = $this->getLocInfo($loc);
                if ($used_api_to_get_tags) {
                    usleep(500000);
                    file_put_contents('query_imported_questions-' . date('Y-m-d') . '.txt', "$loc \r\n", FILE_APPEND);
                } else {
                    file_put_contents('query_skipped_imported_questions-' . date('Y-m-d') . '.txt', "$loc \r\n", FILE_APPEND);
                }
            }
        }
    }

    public function updateTags()
    {
        //get all the ones that must be updated
        $MindTouchEvent = MindTouchEvent::where('status', NULL)->where('event', 'page.tag:update')->get();

        foreach ($MindTouchEvent as $key => $mind_touch_event) {

            DB::beginTransaction();

            try {
                $page_id = $mind_touch_event->page_id;
                $question = Question::where('page_id', $page_id)->first();
                $parsed_url = parse_url($question->location);
                $page_info = $this->getPageInfo($parsed_url);
                usleep(500000);
                $question->tags()->detach();
                $technology_and_tags = $this->getTechnologyAndTags($page_info);
                $this->addTagsToQuestion($question, $technology_and_tags['tags']);
                $mind_touch_event->status = 'updated';
                $mind_touch_event->save();
                DB::commit();
            } catch (Exception $e) {
                DB::rollback();
                Log::error("updateTags failed with page_id $page_id");
            }
        }
    }

    public function getLocInfo($loc)

    {
        try {
            $parsed_url = parse_url($loc);
            if (!isset($parsed_url['path'])) {
                //some were malformed with ?title=Assessment_Gallery instead of /Assessment_Gallery
                $loc = str_replace('?title=Assessment_Gallery', '/Assessment_Gallery', $loc);
                $parsed_url = parse_url($loc);
            }


            $question_exists_in_db = DB::table('questions')->where('location', $loc)->first();
            if ($question_exists_in_db) {
                return false;//didn't use the API
            }
            $page_info = $this->getPageInfo($parsed_url);

            $page_id = $page_info['@id'];
            //file_put_contents('sitemap', "$final_url $page_id \r\n", FILE_APPEND);
            $technology_and_tags = $this->getTechnologyAndTags($page_info);
            $question = Question::firstOrCreate(['page_id' => $page_id,
                'technology' => $technology_and_tags['technology'],
                'location' => $loc]);
            $this->addTagsToQuestion($question, $technology_and_tags['tags']);


        } catch (Exception $e) {
            file_put_contents('query_import_errors-' . date('Y-m-d') . '.txt', $e->getMessage() . ":  $loc \r\n", FILE_APPEND);
        }
        return true;//used the API

    }

    function addTagsToQuestion($question, array $tags)
    {
        $Question = new Question;

        if ($tags) {
            foreach ($tags as $key => $tag) {
                $Question->addTag($tag, mb_strtolower($tag), $question);
            }
        }
    }

    public
    function getTechnologyAndTags($page_info)
    {
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
        return compact('tags', 'technology');
    }

    public
    function getPageInfo(array $parsed_url)
    {
        $host = $parsed_url['host'];
        $path = substr($parsed_url['path'], 1);//get rid of trailing slash
        $library = str_replace('.libretexts.org', '', $host);
        $tokens = $this->tokens;
        $token = $tokens->{$library};
        $headers = ['Origin' => 'https://adapt.libretexts.org', 'x-deki-token' => $token];

        $final_url = "https://$library.libretexts.org/@api/deki/pages/=" . urlencode($path) . '?dream.out.format=json';

        $response = $this->client->get($final_url, ['headers' => $headers]);
        $page_info = json_decode($response->getBody(), true);
        return $page_info;
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

    public
    function getQueryUpdates()
    {
        https://api.libretexts.org/endpoint/queryEvents?limit=1000
        $tokens = $this->tokens;
        $token = $tokens->query;
        $command = 'curl -i -H "Accept: application/json" -H "origin: https://dev.adapt.libretexts.org" -H "x-deki-token: ' . $token . '" https://api.libretexts.org/endpoint/queryEvents?limit=1000';

        exec($command, $output, $return_var);
        if ($return_var > 0) {
            Log::error("getQueryUpdates failed with return_var: $return_var");
            exit;
        }
        $has_summaries = false;
        foreach ($output as $key => $value) {
            if (strpos($value, '<summaries') === 0) {
                $has_summaries = true;
                $xml = simplexml_load_string($value);
                break;
            }
        }
        if (!$has_summaries) {
            Log::error("getQueryUpdates failed because none of the output started with <summaries");
            exit;
        }

        /**
         * echo $value->event['mt-epoch'] . "\r\n";
         * echo $value->event->page->path . "\r\n";
         **/
        foreach ($xml->children() as $key => $value) {

            $page_id = $value->event->page['id'];
            //if the question exists, add it to the database
            if (DB::table('questions')->where('page_id', $page_id)->first()) {
                MindTouchEvent::firstOrCreate(['page_id' => $page_id,
                    'event_time' => date("Y-m-d H:i:s", strtotime($value->event['datetime'])),
                    'event' => $value->event['type']]);
            }
        }
    }
}
