<?php

namespace App;

use App\MindTouchEvent;
use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Traits\MindTouchTokens;
use App\Traits\S3;

use \Exception;

class Libretext extends Model
{

    use MindTouchTokens;
    use S3;

    protected $tags;
    protected $questionIds;
    protected $technologyIds;
    protected $client;
    protected $tokens;

    public function __construct(array $attributes = [])
    {


        $this->client = new Client();
        $this->tokens = $this->getTokens();
        if ($attributes) {
            $this->library = $attributes['library'];
            $this->token = $this->tokens->{$this->library};
        }

    }

    /**
     * @return string[]
     */
    public function libraries()
    {

        return ['Biology' => 'bio',
            'Business' => 'biz',
            'Chemistry' => 'chem',
            'English' => 'eng',
            'Español' => 'espanol',
            'Geology' => 'geo',
            'Humanities' => 'human',
            'K12' => 'k12',
            'Law' => 'law',
            'Mathematics' => 'math',
            'Medicine' => 'med',
            'Physics' => 'phys',
            'Query' => 'query',
            'Social Science' => 'socialsci',
            'Statistics' => 'stats',
            'Workforce' => 'workforce'];
    }

    public function saveQuestionMetaInformation()
    {
        try {
            $questions = Question::all();
            foreach ($questions as $question) {
                $response = Http::get("https://{$question->library}.libretexts.org/@api/deki/pages/{$question->page_id}/contents");
                $xml = simplexml_load_string($response->body());
                var_dump($xml->attributes());
                sleep(1);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }


    /**
     * @param string $library
     * @param int $pageId
     * @return false|mixed|string
     */
    public function getTitleByLibraryAndPageId(string $library, int $pageId)
    {
        $response = Http::get("https://{$library}.libretexts.org/@api/deki/pages/{$pageId}/contents");
        $xml = simplexml_load_string($response->body());
        return $xml->attributes()->title[0];
    }

    /**
     * @param int $page_id
     * @param int $question_id
     * @return mixed|string
     */
    public function updateTitle(int $page_id, int $question_id)
    {
        try {
            $contents = $this->getContentsByPageId($page_id);
            $title = $contents['@title'] ?? 'No title.';
        } catch (Exception $e) {
            try {
                $contents = $this->getPrivatePage('contents', $page_id);
                $attribute = '@title';
                $title = $contents->$attribute;
            } catch (Exception $e) {
                $title = 'No title';
            }
        }
        if (!$title) {
            $title = 'No title';
        }
        Question::where('id', $question_id)->update(['title' => $title]);
        return $title;
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
                    file_put_contents('query_skipped_imported_questions-' . date('Y-m-d') . '.txt', "No api used: git$loc \r\n", FILE_APPEND);
                }
            }
        }
    }

    public function updateTags()
    {
        //update based on either a single event or all possible tag update events
        $MindTouchEvent = MindTouchEvent::where('status', NULL)
            ->where('event', 'page.tag:update')
            ->get();

        foreach ($MindTouchEvent as $key => $mind_touch_event) {

            DB::beginTransaction();

            try {
                $page_id = $mind_touch_event->page_id;
                $question = Question::where('page_id', $page_id)->first();
                $parsed_url = parse_url($question->location);
                $page_info = $this->getPageInfoByParsedUrl($parsed_url);
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


            /*  $question_exists_in_db = DB::table('questions')->where('location', $loc)->first();
             if ($question_exists_in_db) {
                  return false;//didn't use the API
              }
            */
            $page_info = $this->getPageInfoByParsedUrl($parsed_url);

            $page_id = $page_info['@id'];
            $contents = $this->getContentsByPageId($page_id);
            $body = $contents['body'][0];
            if (strpos($body, '<iframe') !== false) {
                //file_put_contents('sitemap', "$final_url $page_id \r\n", FILE_APPEND);
                $technology_and_tags = $this->getTechnologyAndTags($page_info);

                $data = ['page_id' => $page_id,
                    'technology' => $technology_and_tags['technology'],
                    'location' => $loc,
                    'body' => $body];

                $question = Question::firstOrCreate($data);
                $this->addTagsToQuestion($question, $technology_and_tags['tags']);
            } else {
                file_put_contents('query_skipped_imported_questions-' . date('Y-m-d') . '.txt', "$loc \r\n", FILE_APPEND);
            }

        } catch (Exception $e) {
            file_put_contents('query_import_errors-' . date('Y-m-d') . '.txt', $e->getMessage() . ":  $loc \r\n", FILE_APPEND);
        }
        return true;//used the API

    }

    public function getContentsByPageId($page_id)
    {


        $headers = ['Origin' => 'https://adapt.libretexts.org', 'x-deki-token' => $this->token];

        $final_url = "https://{$this->library}.libretexts.org/@api/deki/pages/{$page_id}/contents?dream.out.format=json";

        $response = $this->client->get($final_url, ['headers' => $headers]);
        return json_decode($response->getBody(), true);
    }

    /**
     * @param string $end_point
     * @param int $page_id
     * @return mixed
     * @throws Exception
     */
    function getPrivatePage(string $end_point, int $page_id)
    {
        if (!in_array($end_point, ['contents', 'tags', 'info'])) {
            throw new Exception("$string is not a valid end point.");
        }
        $curl = curl_init();
        $curl_opts = [
            CURLOPT_FAILONERROR => true,
            CURLOPT_URL => "https://api.libretexts.org/endpoint/$end_point",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => '{"path":' . $page_id . ', "subdomain":"' . $this->library . '","mode": "view", "dreamformat":"json"}',
            CURLOPT_HTTPHEADER => [
                "Origin: https://adapt.libretexts.org",
                "Content-Type: text/plain"
            ],
        ];
        curl_setopt_array($curl, $curl_opts);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new Exception (curl_error($curl));
        }
        curl_close($curl);
        return json_decode($response);

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

    /**
     * @param $body
     * @return false|string
     */
    public function getTechnologyFromBody($body)
    {
        if ((strpos($body, 'h5p.libretexts.org') !== false) || (strpos($body, 'studio.libretexts.org/h5p') !== false)) {
            return 'h5p';
        }
        if ((strpos($body, 'webwork.libretexts.org') !== false) || (strpos($body, 'wwrenderer.libretexts.org') !== false)) {
            return 'webwork';
        }
        if (strpos($body, 'imathas.libretexts.org') !== false) {
            return 'imathas';
        }
        return false;
    }

    public function getTechnologyIframeFromBody($body, $technology)
    {

        $domd = new \DOMDocument();
        libxml_use_internal_errors(true);//errors from DOM that I don't care about
        $domd->loadHTML($body);
        libxml_use_internal_errors(false);
        $iframes = $domd->getElementsByTagName('iframe');
        $iframe = '';
        foreach ($iframes as $iframe) {
            if (strpos($iframe->getAttribute('src'), $technology) !== false) {
                break;
            }
        }
        return $domd->saveHTML($iframe);
    }

    public function addGlMolScripts()
    {
        return <<<SCRIPTS

SCRIPTS;

    }

    public function addMathJaxScript()
    {
        $app_url = $this->getAppUrl();

        return <<<MATHJAX
<script type="text/javascript" src="$app_url/assets/js/mathjax.js"></script>
<script type="text/x-mathjax-config">
                    MathJax.Hub.Config({
  messageStyle: "none",
  tex2jax: {preview: "none"}
});
</script>
<script type="text/x-mathjax-config">
  MathJax.Ajax.config.path["mhchem"] =
            "https://cdnjs.cloudflare.com/ajax/libs/mathjax-mhchem/3.3.2";
        MathJax.Hub.Config({ jax: ["input/TeX","input/MathML","output/SVG"],
  extensions: ["tex2jax.js","mml2jax.js","MathMenu.js","MathZoom.js"],
  TeX: {
        extensions: ["autobold.js","mhchem.js","color.js","cancel.js", "AMSmath.js","AMSsymbols.js","noErrors.js","noUndefined.js"]
  },
    "HTML-CSS": { linebreaks: { automatic: true , width: "90%"}, scale: 85, mtextFontInherit: false},
menuSettings: { zscale: "150%", zoom: "Double-Click" },
         SVG: { linebreaks: { automatic: true } }});
</script>

<script type="text/javascript" async="true" src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.3/MathJax.js?config=TeX-AMS_HTML"></script>

MATHJAX;
    }

    public function addExtras(string $body, array $extras)
    {
        $scripts = "['glMol' => " . ($extras['glMol'] ? 1 : 0) . ",'MathJax' => " . ($extras['MathJax'] ? 1 : 0) . "]";
        $php = '<?php $extras = ' . $scripts . '; ?>' . "\r\n";
        $config = "<?php require_once(__DIR__ . '/../libretext.config.php'); ?>\r\n";
        return $php . $config . $body;

    }

    public
    function getTechnologyAndTags($page_info)
    {
        $tags = [];
        $technology = false;
        if (isset($page_info['tags']['tag'])) {
            foreach ($page_info['tags']['tag'] as $key => $value) {
                $tag = $value['@value'] ?? false;
                if ($tag) {
                    if (strpos($tag, 'tech:') === 0) {
                        $technology = str_replace('tech:', '', $tag);
                    } else {
                        $tags[] = strtolower($tag);
                    }
                }
            }
        }
        return compact('tags', 'technology');
    }

    public function getPageInfoByPageId(int $page_id)
    {

        $headers = ['Origin' => 'https://adapt.libretexts.org', 'x-deki-token' => $this->token];

        $final_url = "https://{$this->library}.libretexts.org/@api/deki/pages/{$page_id}/info?dream.out.format=json";

        $response = $this->client->get($final_url, ['headers' => $headers]);
        return json_decode($response->getBody(), true);

    }

    public function getTagsByPageId(int $page_id)
    {
        $headers = ['Origin' => 'https://adapt.libretexts.org', 'x-deki-token' => $this->token];

        $final_url = "https://{$this->library}.libretexts.org/@api/deki/pages/{$page_id}/tags?dream.out.format=json";

        $response = $this->client->get($final_url, ['headers' => $headers]);
        return json_decode($response->getBody(), true);

    }

    public
    function getPageInfoByParsedUrl(array $parsed_url)
    {

        $path = substr($parsed_url['path'], 1);//get rid of trailing slash
        $headers = ['Origin' => 'https://adapt.libretexts.org', 'x-deki-token' => $this->token];

        $final_url = "https://{$this->library}.libretexts.org/@api/deki/pages/=" . urlencode($path) . '?dream.out.format=json';

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


}
