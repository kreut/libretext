<?php

namespace App;

use App\Traits\IframeFormatter;
use App\Traits\LibretextFiles;
use Carbon\Carbon;
use DOMDocument;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\Handler;
use \Exception;

class Question extends Model
{
    use IframeFormatter;
    use LibretextFiles;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        ini_set('memory_limit', '2G');

    }

    public function addTimeToS3Images($contents, DOMDocument $htmlDom, $with_php = true): string
    {
        if (!$contents) {
            return '';
        }

        preg_match_all('/<\?php(.+?)\?>/is', $contents, $php_blocks);
        if ($php_blocks) {
            foreach ($php_blocks[0] as $block) {
                $contents = str_replace($block, '', $contents);
            }
        }

        libxml_use_internal_errors(true);//errors from DOM that I don't care about
        $htmlDom->loadHTML(mb_convert_encoding($contents, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_use_internal_errors(false);
        $imageTags = $htmlDom->getElementsByTagName('img');
        foreach ($imageTags as $imageTag) {
            $imgSrc = $imageTag->getAttribute('src');
            $is_s3_url = strpos($imgSrc, 'amazonaws.com') !== false;
            if ($is_s3_url) {
                $s3_file = strtok(pathinfo($imgSrc, PATHINFO_BASENAME), '?');
                $url = Storage::disk('s3')->temporaryUrl("uploads/images/$s3_file", Carbon::now()->addDays(7));
                $imageTag->setAttribute('src', $url);
            }
        }
        $contents = $htmlDom->saveHTML();
        if ($php_blocks && $with_php) {
            $php = implode('', $php_blocks[0]);
            $contents = $php . $contents;
        }

        return $contents;
    }

    public function sendImgsToS3(int $user_id, string $dir, string $contents, DOMDocument $htmlDom): string
    {

        $efs_dir = '/mnt/local/';
        $is_efs = is_dir($efs_dir);
        $storage_path = $is_efs
            ? $efs_dir
            : Storage::disk('local')->getAdapter()->getPathPrefix();

        libxml_use_internal_errors(true);//errors from DOM that I don't care about
        $htmlDom->loadHTML(mb_convert_encoding($contents, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_use_internal_errors(false);
        $imageTags = $htmlDom->getElementsByTagName('img');
        foreach ($imageTags as $imageTag) {
            $imgSrc = $imageTag->getAttribute('src');
            $extension = pathinfo($imgSrc, PATHINFO_EXTENSION);
            $fileName = uniqid() . time() . '.' . $extension;
            $s3_location = "uploads/images/$fileName";
            $imgSrc = str_replace('$IMS-CC-FILEBASE$/', '', $imgSrc);//for Canvas (at least) this is added
            $imgSrc = str_replace('%20', ' ', $imgSrc);//for Canvas (at least), spaces became '%20'
            Storage::disk('s3')->put($s3_location, file_get_contents("{$storage_path}uploads/qti/$user_id/$dir/$imgSrc"));
            $url = Storage::disk('s3')->temporaryUrl($s3_location, Carbon::now()->addDays(7));
            $imageTag->setAttribute('src', $url);
        }
        return $htmlDom->saveHTML();
    }

    /**
     * @return bool
     */
    public function existsInLearningTree()
    {
//limit search in the database so that the library and page_id exist (may be separate)
        $learning_trees = DB::table('learning_trees')
            ->where('learning_tree', 'LIKE', "%{$this->page_id}%")
            ->where('learning_tree', 'LIKE', "%{$this->library}%")
            ->get('learning_tree')
            ->pluck('learning_tree');
        if ($learning_trees) {
            foreach ($learning_trees as $learning_tree) {
                $blocks = json_decode($learning_tree)->blocks;
                foreach ($blocks as $block) {
                    $page_id_match = false;
                    $library_match = false;
                    foreach ($block->data as $data) {
                        if ($data->name === 'page_id' && (int)$data->value === (int)$this->page_id) {
                            $page_id_match = true;
                        }
                        if ($data->name == 'library' && strtolower($data->value) === strtolower($this->library)) {
                            $library_match = true;
                        }
                    }
                    if ($page_id_match && $library_match) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function getTechnologySrcAndProblemJWT(Request     $request,
                                                  Assignment  $assignment,
                                                  Question    $question,
                                                  string      $seed,
                                                  bool        $show_solutions,
                                                  DOMDocument $domd,
                                                  JWE         $JWE,
                                                  array       $additional_custom_claims = [])
    {


        //set up the problemJWT
        $custom_claims = ['adapt' => [
            'assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'technology' => $question->technology]];
        if ($additional_custom_claims) {
            foreach ($additional_custom_claims as $key => $additional_custom_claim) {
                $custom_claims['adapt'][$key] = $additional_custom_claim;
            }
        }
        $custom_claims['scheme_and_host'] = $request->getSchemeAndHttpHost();
        //if I didn't initialize each, I was getting a weird webwork error
        //in addition, the imathas problem JWT had the webwork info from the previous
        //problem.  Not sure why!  Maybe it has something to do createProblemJWT
        //TymonDesigns keeps the custom claims???
        $custom_claims['imathas'] = [];
        $custom_claims['webwork'] = [];
        $custom_claims['h5p'] = [];
        $problemJWT = '';
        $technology_src = '';
        switch ($question->technology) {

            case('webwork'):

                // $webwork_url = 'webwork.libretexts.org';
                //$webwork_url = 'demo.webwork.rochester.edu';
                // $webwork_base_url = '';

                $webwork_url = 'https://wwrenderer.libretexts.org';
                $webwork_base_url = '';


                $custom_claims['iss'] = $request->getSchemeAndHttpHost();//made host dynamic

                $custom_claims['aud'] = $webwork_url;
                $custom_claims['webwork']['problemSeed'] = $seed;
                switch ($webwork_url) {
                    case('demo.webwork.rochester.edu'):
                        $custom_claims['webwork']['courseID'] = 'daemon_course';
                        $custom_claims['webwork']['userID'] = 'daemon';
                        $custom_claims['webwork']['course_password'] = 'daemon';
                        break;
                    case('webwork.libretexts.org'):
                        $custom_claims['webwork']['courseID'] = 'anonymous';
                        $custom_claims['webwork']['userID'] = 'anonymous';
                        $custom_claims['webwork']['course_password'] = 'anonymous';
                        break;
                }
                /* if (Auth::user()->role === 2
                     && $assignment->course->user_id !== Auth::user()->id
                     && Helper::isCommonsCourse($assignment->course)) {
                     $custom_claims['webwork']['showSubmitButton'] = 0;
                     $custom_claims['webwork']['showPreviewButton'] = 0;
                 }
                */
                if ($webwork_url === 'https://wwrenderer.libretexts.org') {

                    $custom_claims['webwork']['showPartialCorrectAnswers'] = $show_solutions;
                    $custom_claims['webwork']['showSummary'] = $show_solutions;

                    $custom_claims['webwork']['outputFormat'] = 'jwe_secure';
                    // $custom_claims['webwork']['answerOutputFormat'] = 'static';

                    $technology_src = $this->getIframeSrcFromHtml($domd, $question['technology_iframe']);
                    $custom_claims['webwork']['sourceFilePath'] = "pgfiles/" . $this->getQueryParamFromSrc($technology_src, 'sourceFilePath');

                    /*$custom_claims['webwork']['problemSourceURL'] = (substr($this->getQueryParamFromSrc($technology_src, 'sourceFilePath'), 0, 4) !== "http")
                        ? "https://webwork.libretexts.org:8443/pgfiles/"
                        : '';
                    $custom_claims['webwork']['problemSourceURL'] .= $this->getQueryParamFromSrc($technology_src, 'sourceFilePath');
*/

                    $custom_claims['webwork']['JWTanswerURL'] = $request->getSchemeAndHttpHost() . "/api/jwt/process-answer-jwt";

                    $custom_claims['webwork']['problemUUID'] = rand(1, 1000);
                    $custom_claims['webwork']['language'] = 'en';
                    $custom_claims['webwork']['showHints'] = 0;
                    $custom_claims['webwork']['showSolution'] = 0;
                    $custom_claims['webwork']['showDebug'] = 0;

                    $question['technology_iframe'] = '<iframe class="webwork_problem" frameborder=0 src="' . $webwork_url . $webwork_base_url . '/rendered?showSubmitButton=0&showPreviewButton=0" width="100%"></iframe>';
                } else {
                    $custom_claims['webwork']['showSummary'] = 1;
                    $custom_claims['webwork']['displayMode'] = 'MathJax';
                    $custom_claims['webwork']['language'] = 'en';
                    $custom_claims['webwork']['outputformat'] = 'libretexts';
                    $custom_claims['webwork']['showCorrectButton'] = 0;
                    $technology_src = $this->getIframeSrcFromHtml($domd, $question['technology_iframe']);
                    $custom_claims['webwork']['sourceFilePath'] = $this->getQueryParamFromSrc($technology_src, 'sourceFilePath');

                    $custom_claims['webwork']['answersSubmitted'] = '0';
                    $custom_claims['webwork']['displayMode'] = 'MathJax';
                    $custom_claims['webwork']['form_action_url'] = "https://$webwork_url/webwork2/html2xml";
                    $custom_claims['webwork']['problemUUID'] = rand(1, 1000);
                    $custom_claims['webwork']['language'] = 'en';
                    $custom_claims['webwork']['showHints'] = 0;
                    $custom_claims['webwork']['showSolution'] = 0;
                    $custom_claims['webwork']['showDebug'] = 0;
                    $custom_claims['webwork']['showScoreSummary'] = $show_solutions;
                    $custom_claims['webwork']['showAnswerTable'] = $show_solutions;

                    $question['technology_iframe'] = '<iframe class="webwork_problem" frameborder=0 src="https://' . $webwork_url . '/webwork2/html2xml?" width="100%"></iframe>';
                }


                $problemJWT = $this->createProblemJWT($JWE, $custom_claims, 'webwork');

                break;
            case('imathas'):

                $custom_claims['webwork'] = [];
                $custom_claims['imathas'] = [];
                $technology_src = $this->getIframeSrcFromHtml($domd, $question['technology_iframe']);
                $custom_claims['imathas']['id'] = $this->getQueryParamFromSrc($technology_src, 'id');


                $custom_claims['imathas']['seed'] = $seed;
                $custom_claims['imathas']['allowregen'] = false;//don't let them try similar problems
                $question['technology_iframe'] = '<iframe class="imathas_problem" frameborder="0" src="https://imathas.libretexts.org/imathas/adapt/embedq2.php?" height="1500" width="100%"></iframe>';
                $question['technology_iframe'] = '<div id="embed1wrap" style="overflow:visible;position:relative">
 <iframe id="embed1" style="position:absolute;z-index:1" frameborder="0" src="https://imathas.libretexts.org/imathas/adapt/embedq2.php?frame_id=embed1"></iframe>
</div>';
                $problemJWT = $this->createProblemJWT($JWE, $custom_claims, 'webwork');//need to create secret key for imathas as well

                break;
            case('h5p'):
                $technology_src = $this->getIframeSrcFromHtml($domd, $question['technology_iframe']);
                if (Auth::user()->role === 2) {
                    $technology_src = str_replace('/embed', '', $technology_src);
                }
                break;
            case('qti'):
            case('text'):
                break;
            default:
                $response['message'] = "Question id {$question->id} uses the technology '{$question->technology}' which is currently not supported by ADAPT.";
                echo json_encode($response);
                exit;

        }
        return compact('technology_src', 'problemJWT');
    }

    /**
     * @param string $qti_json
     * @param $seed
     * @param bool $show_solution
     * @return false|string
     * @throws Exception
     */
    public function formatQtiJson(string $qti_json, $seed, bool $show_solution)
    {
        $qti_array = json_decode($qti_json, true);
        $question_type = $qti_array['questionType'];

        if (!$show_solution) {
            foreach ($qti_array as $item) {
                unset($qti_array["responseDeclaration"]);
            }
        }
        switch ($question_type) {
            case('true_false'):
                break;
            case('multiple_choice'):
            case('multiple_answers'):
                if ($seed) {
                    $seeds = json_decode($seed, true);
                    $choices_by_identifier = [];
                    $simple_choices = [];

                    foreach ($qti_array['simpleChoice'] as $choice) {
                        if (!$show_solution){
                            unset($choice['feedback']);
                            unset($choice['correctResponse']);
                        }
                        $choices_by_identifier[$choice['identifier']] = $choice;

                    }
                    foreach ($seeds as $identifier) {

                        $simple_choices[] = $choices_by_identifier[$identifier];
                    }
                    $qti_array['simpleChoice'] = $simple_choices;
                }
                break;
            case('select_choice'):
                if (!$show_solution) {
                    foreach ($qti_array['inline_choice_interactions'] as $identifier => $choices) {
                        foreach ($choices as $key => $value) {
                            unset($qti_array['inline_choice_interactions'][$identifier][$key]['correctResponse']);
                        }
                    }
                }
                if ($seed) {
                    $seed = json_decode($seed, true);
                    $inline_choice_interactions = [];
                    foreach ($qti_array['inline_choice_interactions'] as $identifier => $choices) {
                        $inline_choice_interactions[$identifier] = [];
                        foreach ($seed[$identifier] as $order) {
                            $inline_choice_interactions[$identifier][] = $qti_array['inline_choice_interactions'][$identifier][$order];
                        }
                    }
                    $qti_array['inline_choice_interactions'] = $inline_choice_interactions;
                }
                break;
            case('fill_in_the_blank'):
                //nothing to do
                break;
            default:
                throw new Exception("$question_type is not a valid question type.");

        }

        return json_encode($qti_array);


    }

    public
    function createProblemJWT(JWE $JWE, array $custom_claims, string $technology)
    {
        $payload = auth()->payload();
        $secret = $JWE->getSecret($technology);
        \JWTAuth::getJWTProvider()->setSecret($secret); //change the secret
        $token = \JWTAuth::getJWTProvider()->encode(array_merge($custom_claims, $payload->toArray())); //create the token
        $problemJWT = $JWE->encrypt($token, 'webwork'); //create the token
        //put back the original secret
        \JWTAuth::getJWTProvider()->setSecret(config('myconfig.jwt_secret'));
        /*  May help for debugging...
         \Storage::disk('s3')->put('secret.txt',$secret);
         \Storage::disk('s3')->put('webwork.txt',$token);
         \Storage::disk('s3')->put('problem_jwt.txt',$problemJWT);
         \Storage::disk('s3')->put('myconfig.jwt_secret.txt',config('myconfig.jwt_secret'));
        */
        $payload = auth()->payload();

        return $problemJWT;

    }

    public
    function getQueryParamFromSrc(string $src, string $query_param)
    {
        $url_components = parse_url($src);
        parse_str($url_components['query'], $output);
        return $output[$query_param];
    }


    public
    function getIframeSrcFromHtml(\DOMDocument $domd, string $html)
    {
        libxml_use_internal_errors(true);//errors from DOM that I don't care about
        $domd->loadHTML($html);
        libxml_use_internal_errors(false);
        $iFrame = $domd->getElementsByTagName('iframe')->item(0);
        return $iFrame->getAttribute('src');

    }

    /**
     * @param string $technology
     * @param string $technology_id
     * @return string
     */
    public
    function getTechnologyIframeFromTechnology(string $technology, string $technology_id)
    {
        switch ($technology) {
            case('h5p'):
                $technology_iframe = '<iframe src="https://studio.libretexts.org/h5p/' . $technology_id . '/embed" frameborder="0" allowfullscreen="allowfullscreen"></iframe>';
                break;
            case('webwork'):
                $technology_iframe = '<iframe allowtransparency="true" frameborder="0" src="https://webwork.libretexts.org/webwork2/html2xml?answersSubmitted=0&amp;sourceFilePath=' . $technology_id . '&amp;problemSeed=1234567&amp;courseID=anonymous&amp;userID=anonymous&amp;course_password=anonymous&amp;showSummary=1&amp;displayMode=MathJax&amp;problemIdentifierPrefix=102&amp;language=en&amp;outputformat=libretexts&amp;showScoreSummary=0&amp;showAnswerTable=0" width="100%"></iframe>';
                break;
            case('imathas'):
                $technology_iframe = '<iframe src="https://imathas.libretexts.org/imathas/embedq2.php?id=' . $technology_id . '" class="imathas_problem"></iframe>';
                break;
            default:
                $technology_iframe = '';
        }
        return $technology_iframe;
    }

    /**
     * @param string $technology
     * @param string $technology_id
     * @return string
     */
    public
    function getTechnologyURLFromTechnology(string $technology, string $technology_id): string
    {

        switch ($technology) {
            case('h5p'):
                $url = "https://studio.libretexts.org/h5p/$technology_id";
                break;
            case('webwork'):
                $url = "https://webwork.libretexts.org/webwork2/html2xml?answersSubmitted=0&amp;sourceFilePath=$technology_id&amp;problemSeed=1234567&amp;courseID=anonymous&amp;userID=anonymous&amp;course_password=anonymous&amp;showSummary=1&amp;displayMode=MathJax&amp;problemIdentifierPrefix=102&amp;language=en&amp;outputformat=libretexts&amp;showScoreSummary=0&amp;showAnswerTable=0";
                break;
            case('imathas'):
                $url = "https://imathas.libretexts.org/imathas/embedq2.php?id=$technology_id";
                break;
            default:
                $url = '';
        }
        return $url;


    }

    function addTags($tags)
    {
        $this->cleanUpTags();
        if ($tags) {
            foreach ($tags as $tag) {
                $tag = trim($tag);
                $tag_in_db = Tag::where('tag', $tag)->first();
                $tag_id = $tag_in_db
                    ? $tag_in_db->id
                    : Tag::create(['tag' => $tag])->id;
                DB::table('question_tag')->insert(['question_id' => $this->id,
                    'tag_id' => $tag_id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()]);
            }
        }
    }


    function cleanUpTags()
    {
        $question_tags = DB::table('question_tag')->where('question_id', $this->id)->get();
        foreach ($question_tags as $question_tag) {
            $number_of_times_tag_appears = DB::table('question_tag')
                ->where('tag_id', $question_tag->tag_id)
                ->where('question_id', '<>', $this->id)
                ->count();

            //clean up the tags
            DB::table('question_tag')->where('question_id', $this->id)->delete();
            if (!$number_of_times_tag_appears) {
                Tag::where('id', $question_tag->tag_id)->delete();
            }
        }
    }

    /**
     * @param $non_technology_text
     * @param string $library
     * @param string $page_id
     * @param Libretext $libretext
     * @throws FileNotFoundException
     */
    function storeNonTechnologyText($non_technology_text,
                                    string $library,
                                    string $page_id,
                                    Libretext $libretext)
    {
        if ($non_technology_text) {
            $glMol = strpos($non_technology_text, '/Molecules/GLmol/js/GLWrapper.js') !== false;
            $non_technology_text = $libretext->addExtras($non_technology_text,
                ['glMol' => $glMol,
                    'MathJax' => strpos($non_technology_text, 'math-tex') !== false
                        || strpos($non_technology_text, "\(") !== false
                        || strpos($non_technology_text, "(\\") !== false
                ]);
            Storage::disk('s3')->put("$library/$page_id.php", $non_technology_text);
            $efs_dir = '/mnt/local/';
            $is_efs = is_dir($efs_dir);
            $storage_path = $is_efs
                ? $efs_dir
                : Storage::disk('local')->getAdapter()->getPathPrefix();

            $file = "{$storage_path}{$library}/{$page_id}.php";
            shell_exec("rm -rf $file");
        }
    }

    /**
     * @param DOMDocument $domd
     * @param Libretext $libretext
     * @param string $technology_iframe
     * @param int $page_id
     * @return array
     * @throws Exception
     */
    public
    function getQuestionExtras(DOMDocument $domd,
                               Libretext   $libretext,
                               string      $technology_iframe,
                               int         $page_id): array
    {
        $technology = $libretext->getTechnologyFromBody($technology_iframe);

        if ($technology === 'h5p') {
            $domd->loadHTML($technology_iframe);
            $iFrame = $domd->getElementsByTagName('iframe')->item(0);
            $src = $iFrame->getAttribute('src');
            $h5p_id = str_replace(['https://studio.libretexts.org/h5p/', '/embed'], ['', ''], $src);
            $h5p_info = $this->getH5PInfo($h5p_id);
            $author = $h5p_info['author'];
            $license = $h5p_info['license'];
            $license_version = $h5p_info['license_version'];
            $title = $h5p_info['title'];
            $tags = $h5p_info['tags'];
            $notes = $h5p_info['body'];

        } else {
            $license = null;
            $license_version = null;
            $author = null;
            $title = null;
            $notes = null;
            $tags = $libretext->getPrivatePage('tags', $page_id)->tag ?? null;
            if (is_array($tags)) {
                foreach ($tags as $tag) {
                    if (strpos($tag->title, 'author') !== false) {
                        $author = str_replace(['authorname:', 'author:', 'author-'], '', $tag->title);
                    }
                    if (strpos($tag->title, 'license:') !== false) {
                        $license = str_replace('license:', '', $tag->title);
                    }
                    if (strpos($tag->title, 'licenseversion:') !== false) {
                        $license_version = str_replace('licenseversion:', '', $tag->title);
                        $license_version = number_format($license_version / 10, 1);
                    }
                }
            }
        }
        return compact('author', 'license', 'license_version', 'title', 'tags', 'notes');
    }

    /**
     * @param int $h5p_id
     * @return array
     * @throws Exception
     */
    public
    function getH5PInfo(int $h5p_id): array
    {
        $author = null;
        $license = null;
        $license_version = null;
        $title = null;
        $tags = null;
        $url = null;
        $body = null;
        $h5p_type_id = null;
        $endpoint = "https://studio.libretexts.org/api/h5p/$h5p_id";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $output = curl_exec($ch);
        curl_close($ch);
        $success = false;
        if ($info = json_decode($output, 1)) {
            $info = $info[0];
            $h5p_type_id = $info['type_id'] ?? null;
            $body = $info['body'];
            $author = $this->getH5PAuthor($info);
            $license = $this->mapLicenseTextToValue($info['license']);
            $license_version = $license ? $info['license_version'] : null;
            $title = $this->getH5PTitle($info);
            $url = $this->getTechnologyURLFromTechnology('h5p', $h5p_id);
            $tags = $this->getH5PTags($info);
            $success = $info !== [];
        }
        return compact('h5p_type_id', 'author', 'license', 'license_version', 'title', 'url', 'tags', 'success', 'body');
    }

    public
    function getH5PTags($info)
    {
        $tags = $info['field_tags'] ?? null;
        if ($tags) {
            $tags = explode(',', $tags);
        }
        return $tags;
    }

    public
    function getH5PTitle($info)
    {
        $title = $info['title_1'] ?? null;
        if ($title) {
            $title = str_replace(['&quot;', '&amp;quot;', '&amp;amp;'], ['"', '"', 'and'], $title);
        }
        return $title;

    }

    public
    function getH5PAuthor($info)
    {
        $author = json_decode(str_replace(['&quot;', '&amp;amp;'], ['"', 'and'], $info['authors']));
        return !$author ? $info['uid'] : ($author[0]->name ?? null);
    }

    /**
     * @param string $license_text
     * @return string|null
     * @throws Exception
     */
    public
    function mapLicenseTextToValue(string $license_text): ?string
    {
        try {
            $licenses = ['Public Domain' => 'publicdomain',
                'PD' => 'publicdomain',
                'CC0 1.0' => 'publicdomaindedication',
                'U' => 'publicdomain',
                'CC BY' => 'ccby',
                'CC BY-NC' => 'ccbync',
                'CC BY-ND' => 'ccbynd',
                'CC BY-NC-ND' => 'ccbyncnd',
                'CC BY-NC-SA' => 'ccbyncsa',
                'CC BY-SA' => 'ccbysa',
                'GNU GPL' => 'gnu',
                'All Rights Reserved' => 'arr',
                'GNU FDL' => 'gnufdl'];
            if ($license_text && !isset($licenses[$license_text])) {
                throw new Exception("$license_text does not exist; need to update this in the questions view and in the database.");
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return $license_text;
        }
        return $licenses[$license_text] ?? null;
    }

    public
    function getUrlLinkText($url)
    {
        $matches = [];
        preg_match('/\>(.*)<\/a>/', $url, $matches);
        /*** return the match ***/
        return $matches[1];
    }

    public
    function getCreatedAt($time)
    {
        $matches = [];
        preg_match('/\>(.*)<\/time>/', $time, $matches);
        /*** return the match ***/
        return $matches[1];
    }

    public
    function tags()
    {
        return $this->belongsToMany('App\Tag')->withTimestamps();
    }


    public
    function getH5PQuestions(int $offset)
    {
        /** [
         * "<a href=\"https://h5p.libretexts.org/wp-admin/admin.php?page=h5p&task=show&id=1464\">Cap.5: Videos y actividad.</a>",
         * { "id": "H5P.Column", "title": "Column" },
         * { "id": "14", "title": "Anaid Stere-Lugo" },
         * [ { "id": "686", "title": "la ropa" }, { "id": "689", "title": "de compras" }, { "id": "691", "title": "adjetivos." }, { "id": "697", "title": "Video" } ],
         * "<time datetime=\"2020-07-11T02:44:46+00:00\" title=\"2 days ago\">2020/07/11</time>",
         * "1464",
         * "<a href=\"https://h5p.libretexts.org/wp-admin/admin.php?page=h5p&task=results&id=1464\">Results</a>",
         * "<a href=\"https://h5p.libretexts.org/wp-admin/admin.php?page=h5p_new&id=1464\">Edit</a>" ]
         */

        $login_user = getenv('H5P_USERNAME');
        $login_pass = getenv('H5P_PASSWORD');
        $login_url = 'https://h5p.libretexts.org/wp-login.php';
        $visit_url = "https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_contents&limit=100&offset=$offset&sortBy=4&sortDir=0";
        $cookie_file = '/cookie.txt';
        $http_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6";

        $fields_string = http_build_query(['log' => $login_user,
            'pwd' => $login_pass,
            'wp-submit' => 'Log%20In',
            'redirect_to' => $visit_url]);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $login_url);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $http_agent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_REFERER, $login_url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_POST, 1);
        $questions = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        }
        curl_close($ch);

        if (isset($error_msg)) {
            echo $error_msg;
            exit;
        }
        return json_decode($questions);
    }

    public
    function addTag($key, $tag, $question)
    {
        if ($key) {
            $tag = Tag::firstOrCreate(compact('tag'));
            if (!$question->tags->contains($tag->id)) {
                $question->tags()->attach($tag->id);
            }
        }
    }

    public
    function storeWebwork()
    {
        try {
            $webwork = DB::connection('webwork');
            echo "Connected to webwork\r\n";
            $questions = $webwork->table('OPL_path')
                ->join('OPL_pgfile', 'OPL_path.path_id', '=', 'OPL_pgfile.path_id')
                ->leftJoin('OPL_pgfile_keyword', 'OPL_pgfile_keyword.pgfile_id', '=', 'OPL_pgfile.pgfile_id')
                ->leftJoin('OPL_keyword', 'OPL_pgfile_keyword.keyword_id', '=', 'OPL_keyword.keyword_id')
                ->leftJoin('OPL_author', 'OPL_author.author_id', '=', 'OPL_pgfile.author_id')
                ->leftJoin('OPL_section', 'OPL_pgfile.DBsection_id', '=', 'OPL_section.section_id')
                ->leftJoin('OPL_chapter', 'OPL_section.chapter_id', '=', 'OPL_chapter.chapter_id')
                ->leftJoin('OPL_textbook', 'OPL_chapter.chapter_id', '=', 'OPL_textbook.textbook_id')
                ->select('keyword',
                    'level',
                    'path',
                    DB::raw("CONCAT(`firstname`,' ',`lastname`) AS author"),
                    DB::raw("CONCAT(`path`,'/',`filename`) AS page_id"),
                    DB::raw("CONCAT(`title`,' - ',OPL_chapter.name,' - ',OPL_section.name,': ',`firstname`,' ',`lastname`) AS textbook_source"))
                ->get();
            DB::disconnect('webwork');
            echo count($questions) . " questions\r\n";
            echo "Disconnected from webwork\r\n";
            echo "Selected questions\r\n";
            foreach ($questions as $value) {
                $data = ['author' => $value->author,
                    'page_id' => $value->page_id,
                    'technology' => 'webwork'];
                $question = Question::firstOrCreate($data);
                $this->addTag($value->keyword, mb_strtolower($value->keyword), $question);
                $this->addTag($value->level, "Difficulty Level = {$value->level}", $question);
                $this->addTag($value->textbook_source, $value->textbook_source, $question);
                $this->addTag($value->path, $value->path, $question);
                $question->refresh();
                echo $value->page_id . "\r\n";
            }
            echo "Inserted questions\r\n";
        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }

    public
    function storeH5P()
    {
        $offset = 0;
        $questions = $this->getH5PQuestions($offset);
        while ($questions->rows) {
            echo $offset;
            foreach ($questions->rows as $question) {
                $title = $this->getUrlLinkText($question[0]);
                $author = $question[2]->title;
                $tag_info = $question[3];
                //$created_at = $this->getCreatedAt($question[4]);  Do I need this?
                $page_id = $question[5];
                $data = compact('title', 'author', 'page_id') + ['technology' => 'h5p'];
                $question = $this->firstOrCreate($data);
                if ($tag_info) {
                    foreach ($tag_info as $value) {
                        $tag_id = Tag::firstOrCreate(['tag' => mb_strtolower($value->title)]);
                        if (!$question->tags->contains($tag_id)) {
                            $question->tags()->attach($tag_id);
                        }
                    }

                    //store question info in the question table
                    //title, author, id, created at, question_and_tag_pivot_id
                    //store the tags in the tag table if they don't already exist

                }
            }
            $offset += 100;
            $questions = $this->getH5PQuestions($offset);
        }
        echo "\r\n";

    }

    public
    function store()
    {
        $this->storeWebwork();
        $this->storeH5P();
    }

    function refreshProperties()
    {
        $Libretext = new Libretext(['library' => $this->library]);
        $body_technology_and_tags_info = $this->getBodyTechnologyAndTagsByPageId($Libretext, $this->page_id);
        $dom_elements_from_body = $this->getDomElementsFromBody($body_technology_and_tags_info['body']);
        $this->text_question = $dom_elements_from_body['text_question'];
        $this->solution_html = $dom_elements_from_body['solution_html'];
        $this->hint = $dom_elements_from_body['hint'];
        $this->libretexts_link = $dom_elements_from_body['libretexts_link'];
        $this->notes = $dom_elements_from_body['notes'];
        $this->save();
    }


    /**
     * @param int $page_id
     * @param string $library
     * @param bool $cache_busting
     * @return array
     * @throws Exception
     */
    public
    function getQuestionIdsByPageId(int $page_id, string $library, bool $cache_busting)
    {
        $question = Question::where('page_id', $page_id)
            ->where('library', $library)
            ->first();

        if ($question && !$cache_busting) {
            return [$question->id]; ///just part of the search....
        }

        //either it's not a question or it is a question and we want to bust the cache

        //maybe it was just created and doesn't exist yet...
        ///get it from query
        ///enter it into the database if I can get it
        ///
        /// getPageInfoByPageId(int $page_id)
        $Libretext = new Libretext(['library' => $library]);

        $body_technology_and_tags_info = $this->getBodyTechnologyAndTagsByPageId($Libretext, $page_id);
        $body = $body_technology_and_tags_info['body'];
        $technology_and_tags = $body_technology_and_tags_info['technology_and_tags'];


        $dom_elements_from_body = $this->getDomElementsFromBody($body);
        $dom = $dom_elements_from_body['dom'];
        $body = $dom_elements_from_body['body'];
        $text_question = $dom_elements_from_body['text_question'];
        $answer_html = $dom_elements_from_body['answer_html'];
        $solution_html = $dom_elements_from_body['solution_html'];
        $hint = $dom_elements_from_body['hint'];
        $libretexts_link = $dom_elements_from_body['libretexts_link'];
        $notes = $dom_elements_from_body['notes'];


        try {
            if ($technology = $Libretext->getTechnologyFromBody($body)) {
                $technology_iframe = $Libretext->getTechnologyIframeFromBody($body, $technology);

                $non_technology = str_replace($technology_iframe, '', $body);
                $has_non_technology = trim($non_technology) !== '';

                if ($has_non_technology) {
                    //Frankenstein type problem
                    $non_technology = $Libretext->addExtras($non_technology,
                        ['glMol' => strpos($body, '/Molecules/GLmol/js/GLWrapper.js') !== false,
                            'MathJax' => false]);
                    Storage::disk('s3')->put("$library/{$page_id}.php", $non_technology);
                }
            } else {
                $technology_iframe = '';
                $has_non_technology = true;
                $non_technology = $Libretext->addExtras($body,
                    ['glMol' => false,
                        'MathJax' => true
                    ]);
                $technology = 'text';
                Storage::disk('s3')->put("$library/{$page_id}.php", $non_technology);
            }


            $question_extras = $this->getQuestionExtras($dom,
                $Libretext,
                $technology_iframe,
                $page_id);

            $question = Question::updateOrCreate(
                ['page_id' => $page_id, 'library' => $library],
                ['technology' => $technology,
                    'title' => null, //I'll get the title below
                    'non_technology' => $has_non_technology,
                    'author' => $question_extras['author'],
                    'license' => $question_extras['license'],
                    'license_version' => $question_extras['license_version'],
                    'technology_iframe' => $technology_iframe,
                    'text_question' => $text_question,
                    'answer_html' => $answer_html,
                    'solution_html' => $solution_html,
                    'hint' => $hint,
                    'libretexts_link' => $libretexts_link,
                    'notes' => $technology === 'h5p' ? $question_extras['notes'] : $notes]);

            $Libretext = new Libretext(['library' => $library]);
            $title = $Libretext->getTitle($page_id);
            $url = $Libretext->getUrl($page_id);
            $question->cached = !$cache_busting;
            $question->title = $title;
            $question->url = $url;
            $question->save();


            if ($technology_and_tags['tags']) {
                $Libretext->addTagsToQuestion($question, $technology_and_tags['tags']);
            }
            return [$question->id];
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            echo json_encode(['type' => 'error',
                'message' => 'We tried saving that page with page id ' . $page_id . ' but got the error: <br><br>' . $e->getMessage() . '<br><br>Please email support with questions!',
                'timeout' => 12000]);
            exit;
        }


    }

    function getBodyTechnologyAndTagsByPageId(Libretext $Libretext, int $page_id)
    {
        $technology_and_tags['technology'] = false;
        $technology_and_tags['tags'] = [];
        try {
            // id=102629;  //Frankenstein test
            //Public type questions
            $page_info = $Libretext->getPageInfoByPageId($page_id);
            $technology_and_tags = $Libretext->getTechnologyAndTags($page_info);
            $contents = $Libretext->getContentsByPageId($page_id);
            $body = $contents['body'][0];
            if (!$body) {
                throw new exception ("This page has no HTML.");
            }
        } catch (Exception $e) {

            if (strpos($e->getMessage(), '403 Forbidden') === false) {
                //some other error besides forbidden
                echo json_encode(['type' => 'error',
                    'message' => 'We tried getting the public page with page id ' . $page_id . ' but got the error: <br><br>' . $e->getMessage() . '<br><br>Please email support with questions!',
                    'timeout' => 12000]);
                exit;
            }

            //private page so try again!
            try {
                $contents = $Libretext->getPrivatePage('contents', $page_id);
                $body = $contents->body;
                $body = $body[0];
                if (!$body) {
                    throw new exception ("This page has no HTML.");
                }
            } catch (Exception $e) {
                $h = new Handler(app());
                $h->report($e);
                echo json_encode(['type' => 'error',
                    'message' => 'We tried getting that private page with page id ' . $page_id . ' but got the error: <br><br>' . $e->getMessage() . '<br><br>Please email support with questions!',
                    'timeout' => 12000]);
                exit;
            }
        }
        return compact('body', 'technology_and_tags');
    }


    function getDomElementsFromBody($body)
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($body);
        libxml_clear_errors();
        $selector = new \DOMXPath($dom);


        $text_question = $this->getInnerHTMLByClass($selector, 'ADAPT-TextQuestion');

        $answer_html = $this->getInnerHTMLByClass($selector, 'ADAPT-Answer');
        $solution_html = $this->getInnerHTMLByClass($selector, 'ADAPT-Solution');
        $hint = $this->getInnerHTMLByClass($selector, 'ADAPT-Hint');
        $libretexts_link = $this->getInnerHTMLByClass($selector, 'ADAPT-Link');
        $notes = $this->getInnerHTMLByClass($selector, 'ADAPT-Notes');

        $classes_to_remove = ['ADAPT-hidden',
            'ADAPT-TextQuestion',
            'ADAPT-A11yQuestion',
            'ADAPT-Answer',
            'ADAPT-Solution',
            'ADAPT-Hint',
            'ADAPT-Link',
            'ADAPT-Notes'];
        foreach ($classes_to_remove as $class_to_remove) {
            foreach ($selector->query('//div[contains(attribute::class, "' . $class_to_remove . '")]') as $e) {
                $e->parentNode->removeChild($e);
            }

        }

        // $body = $doc->saveHTML($doc->documentElement);
        $rootnode = $dom->getELementsByTagName('body')->item(0);
        $body = trim($this->DOMinnerHTML($rootnode));

        return compact('dom',
            'body',
            'text_question',
            'answer_html',
            'solution_html',
            'hint',
            'libretexts_link',
            'notes');

    }

    function getInnerHTMLByClass($selector, $class)
    {
        $innerHTML = null;
        $nodes = $selector->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $class ')]")->item(0);

        if (!$nodes) {
            return null;
        }
        $tmp_dom = new DOMDocument();
        foreach ($nodes->childNodes as $node) {
            $tmp_dom->appendChild($tmp_dom->importNode($node, true));
        }
        $innerHTML .= trim($tmp_dom->saveHTML());
        return $innerHTML;
    }

    function DOMinnerHTML(\DOMNode $element)
    {
        $innerHTML = "";
        $children = $element->childNodes;

        foreach ($children as $child) {
            $innerHTML .= $element->ownerDocument->saveHTML($child);
        }

        return $innerHTML;
    }

    /**
     * @param Request $request
     * @param Libretext $libretext
     * @return array
     * @throws Exception
     */
    function getQuestionToAddByPageId(Request   $request,
                                      Libretext $libretext): array
    {


        $libraries = $libretext->libraries();
        $library_text_page_id = $request->direct_import;
        $library_texts = [];
        $response['type'] = 'error';
        foreach ($libraries as $library_text => $library) {
            $library_texts[] = strtolower($library_text);
        }

        $default_import_library = $request->cookie('default_import_library');
        $library_text_exists = strpos($library_text_page_id, '-') !== false;
        if (!$library_text_exists && !$default_import_library) {
            $response['message'] = "$library_text_page_id should be of the form {library}-{page id}.";
            return $response;
        }

        if ($default_import_library && !$library_text_exists) {
            $library = $default_import_library;
            $page_id = trim($library_text_page_id);
            $library_text = strtolower($this->getLibraryTextFromLibrary($libraries, $library));
        } else {
            [$library_text, $page_id] = explode('-', $library_text_page_id);
            $library_text = strtolower($this->getLibraryTextFromLibrary($libraries, $library_text));
            if (!in_array($library_text, $library_texts)) {
                $response['message'] = "$library_text is not a valid library.";
                return $response;
            }

            $library = $this->getLibraryFromLibraryText($libraries, $library_text);


        }
        if (!(is_numeric($page_id) && is_int(0 + $page_id) && 0 + $page_id > 0)) {
            $response['message'] = "$page_id should be a positive integer.";
            return $response;
        }

        $question_id = $this->getQuestionIdsByPageId($page_id, $library, false)[0];//returned as an array
        $response['question_id'] = $question_id;
        $response['direct_import_id'] = "$library_text-$page_id";
        $response['type'] = 'success';

        return $response;
    }

    /**
     * @param Request $request
     * @return array
     */
    public
    function getQuestionToAddByAdaptId(Request $request)
    {
        $adapt_id = $request->direct_import;
        $response['type'] = 'error';
        $assignment_question_arr = explode('-', $adapt_id);
        if (count($assignment_question_arr) > 2) {
            $response['message'] = "Your ADAPT ID should either be a single number or should be of the form {assignment_id}-{question_id}.";
            return $response;
        }
        if (count($assignment_question_arr) == 2) {
            $assignment_id = $assignment_question_arr[0];
            $question_id = $assignment_question_arr[1];
            $assignment_question = DB::table('assignment_question')
                ->where('assignment_id', $assignment_id)
                ->where('question_id', $question_id)
                ->first();
            if (!$assignment_question) {
                $response['message'] = "$assignment_id-$question_id is not a valid ADAPT ID.";
                return $response;
            }
        } else {
            $assignment_question = null;
            $question = Question::where('id', $assignment_question_arr[0])->where('version', 1)->first();
            if (!$question) {
                $response['message'] = "$assignment_question_arr[0] is not a valid Question ID.";
                return $response;
            }
            $question_id = $question->id;
        }

        $response['question_id'] = $question_id;
        $response['assignment_question'] = $assignment_question;
        $response['direct_import_id'] = $adapt_id;
        $response['type'] = 'success';
        return $response;
    }


    /**
     * @param $libraries
     * @param $library
     * @return int|string
     */
    public
    function getLibraryTextFromLibrary($libraries, $library)
    {
        $response = $library;
        foreach ($libraries as $library_text => $value) {
            if ($value === trim($library)) {
                $response = $library_text;
            }
        }
        return $response;
    }


    /**
     * @param $libraries
     * @param $library_text
     * @return false|mixed|string
     */
    public
    function getLibraryFromLibraryText($libraries, $library_text)
    {
        $response = false;
        foreach ($libraries as $key => $library) {
            //works for the name or abbreviations
            if (strtolower($key) === $library_text || $library === $library_text) {
                $response = $library;
            }
        }
        return $response;
    }

    public
    function cleanUpExtraHtml(DOMDocument $dom, $html): ?string
    {
        libxml_use_internal_errors(true);
        $html = $this->addTimeToS3Images($html, $dom);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $selector = new \DOMXPath($dom);
        foreach ($selector->query('//h2[contains(attribute::class, "editable")]') as $e) {
            $e->parentNode->removeChild($e);
        }
        $dom->saveHTML($dom->documentElement);
        return $this->getInnerHTMLByClass($selector, 'mt-section');

    }

    /**
     * @return bool
     */
    public
    function questionExistsInOneOfTheirAssignments(): bool
    {
        return DB::table('assignment_question')
            ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
            ->join('courses', 'assignments.course_id', '=', 'courses.id')
            ->where('user_id', auth()->user()->id)
            ->where('question_id', $this->id)
            ->exists();

    }

    /**
     * @return bool
     */
    public
    function questionExistsInAnotherInstructorsAssignments(): bool
    {
        return DB::table('assignment_question')
            ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
            ->join('courses', 'assignments.course_id', '=', 'courses.id')
            ->where('user_id', '<>', auth()->user()->id)
            ->where('question_id', $this->id)
            ->exists();

    }

    public
    function cacheQuestionFromLibraryByPageId(string $library, int $page_id)
    {
        $question = $this->where('library', $library)->where('page_id', $page_id)->first();
        if (!$question) {
            $question_id = $this->getQuestionIdsByPageId($page_id, $library, false)[0];
            $question = $this->find($question_id);
        }
        return $question;
    }

    /**
     * @param object $question_info
     * @return array
     */
    public
    function formatQuestionFromDatabase(object $question_info): array
    {
        $question['title'] = $question_info['title'];
        $question['id'] = $question_info['id'];
        $question['iframe_id'] = $this->createIframeId();
        $question['technology'] = $question_info['technology'];
        $question['non_technology'] = $question_info['non_technology'];
        $question['non_technology_iframe_src'] = $this->getLocallySavedPageIframeSrc($question_info);
        $question['technology_iframe'] = $question_info['technology_iframe'];
        $question['technology_iframe_src'] = $this->formatIframeSrc($question_info['technology_iframe'], $question['iframe_id']);
        $question['qti_json'] = $question_info['qti_json'];

        if ($question_info['technology'] === 'webwork') {
            //since it's the instructor, show the answer stuff
            $question['technology_iframe_src'] = str_replace('&amp;showScoreSummary=0&amp;showAnswerTable=0',
                '',
                $question['technology_iframe_src']);
        }
        $question['text_question'] = $question_info['text_question'];
        $question['libretexts_link'] = $question_info['libretexts_link'];

        $question['notes'] = $question['answer_html'] = $question['solution_html'] = $question['hint'] = null;
        if (Auth::user()->role === 2) {
            $question['notes'] = $question_info['notes'];
            $question['answer_html'] = $question_info['answer_html'];
            $question['solution_html'] = $question_info['solution_html'];
            $question['hint'] = $question_info['hint'];
        }

        return $question;
    }

    /**
     * @param $qti_json
     * @param $prompt
     * @param $question_id
     * @return int
     */
    public
    function qtiSimpleChoiceQuestionExists($qti_json, $prompt, $question_id): int
    {
        $stripped_prompt = trim(strip_tags($prompt));
        $like_questions = DB::table('questions')
            ->where('qti_json', 'like', "%" . $stripped_prompt ."%");
        if ($question_id) {
            $like_questions = $like_questions->where('id', '<>', $question_id);
        }
        $like_questions = $like_questions->get();
        $qti_json = json_decode($qti_json, true);
//not checking the double issue for questions with images

        $simple_choices = $this->getSimpleChoices($qti_json);
        $like_question_id = 0;
        foreach ($like_questions as $like_question) {
            $like_question_json = json_decode($like_question->qti_json, true);
            $stripped_like_prompt = trim(strip_tags($like_question_json['prompt']));
            $like_simple_choices = $this->getSimpleChoices($like_question_json);
            if ($stripped_like_prompt === $stripped_prompt && $this->array_values_identical($simple_choices, $like_simple_choices)) {
                $like_question_id = $like_question->id;
            }
        }
        return $like_question_id;
    }

    /**
     * @param $a
     * @param $b
     * @return bool
     */
    function array_values_identical($a, $b): bool
    {
        $x = array_values($a);
        $y = array_values($b);

        sort($x);
        sort($y);

        return $x === $y;
    }

    /**
     * @param $qti_json
     * @return array
     */
    function getSimpleChoices($qti_json): array
    {
        $simpleChoice = $qti_json['simpleChoice'];
        $simple_choices = [];
        foreach ($simpleChoice as $simple_choice) {
            $simple_choices[] = trim(strip_tags($simple_choice['value']));
        }
        return $simple_choices;
    }


}

