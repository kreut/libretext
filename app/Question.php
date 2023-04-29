<?php

namespace App;

use App\Helpers\Helper;
use App\Traits\IframeFormatter;
use App\Traits\LibretextFiles;
use Carbon\Carbon;
use DOMDocument;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

    /**
     * @return string[]
     */
    function getValidLicenses(): array
    {
        return ['publicdomain',
            'publicdomaindedication',
            'ccby',
            'ccbynd',
            'ccbync',
            'ccbyncnd',
            'ccbyncsa',
            'ccbysa',
            'gnu',
            'arr',
            'gnufdl',
            'imathascomm',
            'ck12foundation',
            'ncbsn'];
    }

    /**
     * @param $rubric_categories
     * @return void
     */
    public function addRubricCategories($rubric_categories)
    {
        foreach ($rubric_categories as $rubric_category) {
            $rubricCategory = !isset($rubric_category['id'])
                ? new RubricCategory()
                : RubricCategory::find($rubric_category['id']);
            $rubricCategory->category = $rubric_category['category'];
            $rubricCategory->criteria = $rubric_category['criteria'];
            $rubricCategory->percent = $rubric_category['percent'];
            $rubricCategory->order = $rubric_category['order'];
            $rubricCategory->question_id = $this->id;
            $rubricCategory->save();
        }
    }

    /**
     * @return HasMany
     */
    public function rubricCategories(): HasMany
    {
        return $this->hasMany('App\RubricCategory')->orderBy('order');
    }


    /**
     * @param array $question_ids
     * @return array
     */
    public function formativeQuestions(array $question_ids): array
    {
        return DB::table('assignment_question')
            ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
            ->join('courses', 'assignments.course_id', '=', 'courses.id')
            ->whereIn('question_id', $question_ids)
            ->where(
                function ($query) {
                    return $query
                        ->where('courses.formative', '=', 1)
                        ->orWhere('assignments.formative', '=', 1);
                })
            ->get('question_id')
            ->pluck('question_id')
            ->toArray();
    }

    function folderIdRequired($user, $question_editor_user_id): bool
    {
        if ($user->isDeveloper() || $user->isMe() || $user->role === 5) {
            return $question_editor_user_id === $user->id;
        }
        return true;
    }

    /**
     * @param int $question_id
     * @return Model|Builder|object|null
     */
    public function getQuestionEditorInfoByQuestionId(int $question_id)
    {
        return DB::table('questions')
            ->join('users', 'questions.question_editor_user_id', '=', 'users.id')
            ->select('first_name', 'email')
            ->where('questions.id', $question_id)
            ->first();
    }

    /**
     * @throws Exception
     */
    public function autoImportH5PQuestions(): array
    {

        $ch = curl_init();
        $endpoint = "https://studio.libretexts.org/api/author/" . auth()->user()->email;
        $username = config('myconfig.h5p_api_username');
        $password = config('myconfig.h5p_api_password');
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);

        $result = curl_exec($ch);
        $error_msg = curl_errno($ch) ? curl_error($ch) : '';
        $response['type'] = 'error';
        try {
            if ($error_msg) {
                $user = auth()->user()->first_name . ' ' . auth()->user()->last_name;
                $message = "Could not import H5P for $user: $error_msg";
                throw new Exception ($message);
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            return $response;
        }
        $h5p_author_questions = json_decode($result, true);
        $h5p_author_technology_ids = [];
        if ($h5p_author_questions) {
            foreach ($h5p_author_questions as $question) {
                $h5p_author_technology_ids[] = $question['h5p_id'];
            }
        }
        //these were already imported to the author's account once
        Question::where('technology', 'h5p')
            ->where('question_editor_user_id', auth()->user()->id)
            ->whereIn('technology_id', $h5p_author_technology_ids)
            ->where('h5p_owner_imported', null)
            ->update(['h5p_owner_imported' => 1]);

        $not_owned_questions = Question::where('technology', 'h5p')
            ->where('question_editor_user_id', '<>', auth()->user()->id)
            ->whereIn('technology_id', $h5p_author_technology_ids)
            ->select('id', 'technology_id', 'h5p_owner_imported')
            ->get();
        $in_adapt_technology_ids = array_unique(Question::where('technology', 'h5p')
            ->get('technology_id')
            ->pluck('technology_id')
            ->toArray());
        $h5p_author_technology_ids = array_unique($h5p_author_technology_ids);
        $not_in_adapts = array_unique(array_diff($h5p_author_technology_ids, $in_adapt_technology_ids));
        if ($not_owned_questions->isNotEmpty() || $not_in_adapts) {
            $h5p_import_folder = DB::table('saved_questions_folders')
                ->where('type', 'my_questions')
                ->where('name', 'H5P Imports')
                ->where('user_id', auth()->user()->id)
                ->first();
            if ($h5p_import_folder) {
                $h5p_import_folder_id = $h5p_import_folder->id;
            } else {
                $savedQuestionFolder = new SavedQuestionsFolder();
                $savedQuestionFolder->type = 'my_questions';
                $savedQuestionFolder->name = 'H5P Imports';
                $savedQuestionFolder->user_id = auth()->user()->id;
                $savedQuestionFolder->save();
                $h5p_import_folder_id = $savedQuestionFolder->id;
            }
            foreach ($not_owned_questions as $not_owned_question) {
                if (!$not_owned_question->h5p_owner_imported) {
                    $not_owned_question->question_editor_user_id = auth()->user()->id;
                    $not_owned_question->folder_id = $h5p_import_folder_id;
                    $not_owned_question->h5p_owner_imported = 1;
                    $not_owned_question->save();
                }
            }

            foreach ($not_in_adapts as $not_in_adapt_technology_id) {
                $this->storeImportedH5P(auth()->user()->id, (int)$not_in_adapt_technology_id, $h5p_import_folder_id);
            }
        }

        $response['type'] = 'success';
        return $response;
    }

    public
    function addTimeToS3Images($contents, DOMDocument $htmlDom, $with_php = true): string
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
        $header_tags = ['h1', 'h2', 'h3'];//weird PHP bug screwing up tags by removing the closing one

        foreach ($header_tags as $header_tag) {
            $pattern = "/<$header_tag>(.*?)<\/$header_tag>/i";
            $contents = preg_replace_callback($pattern, function ($match) use ($header_tag) {
                return "ADAPT-$header_tag-start$header_tag" . $match[1] . "ADAPT-$header_tag-end";
            }, $contents);
        }

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

        foreach ($header_tags as $header_tag) {
            $pattern = "/ADAPT-$header_tag-start$header_tag(.*?)ADAPT-$header_tag-end/i";
            $contents = preg_replace_callback($pattern, function ($match) use ($header_tag) {
                return "<$header_tag>" . $match[1] . "</$header_tag>";
            }, $contents);
        }

        if ($php_blocks && $with_php) {
            $php = implode('', $php_blocks[0]);
            $contents = $php . $contents;
        }

        return $contents;
    }

    public
    function sendImgsToS3(int $user_id, string $dir, string $contents, DOMDocument $htmlDom): string
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
            $is_canvas_image_to_fix = true;
            foreach (['assessment_questions', 'files', 'download?verifier'] as $key) {
                if (strpos($imgSrc, $key) === false) {
                    $is_canvas_image_to_fix = false;
                }
            }
            if (strpos($imgSrc, 'instructure.com/equation_images') !== false) {
                $is_canvas_image_to_fix = true;
            }
            if ($is_canvas_image_to_fix) {
                DB::table('not_yet_uploaded_canvas_images')->insert([
                    'user_id' => $user_id,
                    'image_src' => $imgSrc,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                continue;
            }
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
    public
    function existsInLearningTree()
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

    public
    function getTechnologySrcAndProblemJWT(Request     $request,
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
                $is_dev_or_local = app()->environment('dev') || app()->environment('local');
                $webwork_domain = $this->getWebworkDomain();


                $custom_claims['iss'] = $request->getSchemeAndHttpHost();//made host dynamic

                $custom_claims['aud'] = $webwork_domain;
                $custom_claims['webwork']['problemSeed'] = $seed;
                switch ($webwork_domain) {
                    case('demo.webwork.rochester.edu'):
                        $custom_claims['webwork']['courseID'] = 'daemon_course';
                        $custom_claims['webwork']['userID'] = 'daemon';
                        $custom_claims['webwork']['course_password'] = 'daemon';
                        break;
                    case('webwork-staging.libretexts.org'):
                    case('webwork.libretexts.org'):
                        //nothing to add here
                        break;
                }
                /* if (Auth::user()->role === 2
                     && $assignment->course->user_id !== Auth::user()->id
                     && Helper::isCommonsCourse($assignment->course)) {
                     $custom_claims['webwork']['showSubmitButton'] = 0;
                     $custom_claims['webwork']['showPreviewButton'] = 0;
                 }
                */
                if (in_array($webwork_domain, ['https://wwrenderer.libretexts.org', 'https://wwrenderer-staging.libretexts.org'])) {

                    $custom_claims['webwork']['hideAttemptsTable'] = 1;
                    $custom_claims['webwork']['showSummary'] = 0;
                    $custom_claims['webwork']['outputFormat'] = 'jwe_secure';

                    $custom_claims['webwork']['showSolutions'] = $show_solutions || $request->user()->role === 2;
                    // $custom_claims['webwork']['answerOutputFormat'] = 'static';
                    if (!$question['technology_iframe']) {
                        $custom_claims['webwork']['sourceFilePath'] = $question['technology_id'];
                    } else {
                        $technology_src = $this->getIframeSrcFromHtml($domd, $question['technology_iframe']);
                        $custom_claims['webwork']['sourceFilePath'] = $this->getQueryParamFromSrc($technology_src, 'sourceFilePath');
                    }
                    $custom_claims['webwork']['JWTanswerURL'] = $request->getSchemeAndHttpHost() . "/api/jwt/process-answer-jwt";

                    $custom_claims['webwork']['problemUUID'] = rand(1, 1000);
                    $custom_claims['webwork']['language'] = 'en';
                    $custom_claims['webwork']['showHints'] = 0;
                    $custom_claims['webwork']['showSolution'] = 0;
                    $custom_claims['webwork']['showDebug'] = 0;
                    $endpoint = $this->getWebworkEndpoint();
                    $question['technology_iframe'] = '<iframe class="webwork_problem" frameborder=0 src="' . $webwork_domain . "/" . $endpoint . '?showSubmitButton=0&showPreviewButton=0" width="100%"></iframe>';


                } else {
                    $custom_claims['webwork']['showSummary'] = 0;
                    $custom_claims['webwork']['displayMode'] = 'MathJax';
                    $custom_claims['webwork']['language'] = 'en';
                    $custom_claims['webwork']['outputformat'] = 'libretexts';
                    $custom_claims['webwork']['showCorrectButton'] = 0;
                    $technology_src = $this->getIframeSrcFromHtml($domd, $question['technology_iframe']);
                    $custom_claims['webwork']['sourceFilePath'] = $this->getQueryParamFromSrc($technology_src, 'sourceFilePath');

                    $custom_claims['webwork']['answersSubmitted'] = '0';
                    $custom_claims['webwork']['displayMode'] = 'MathJax';
                    $custom_claims['webwork']['form_action_url'] = "https://$webwork_domain/webwork2/html2xml";
                    $custom_claims['webwork']['problemUUID'] = rand(1, 1000);
                    $custom_claims['webwork']['language'] = 'en';
                    $custom_claims['webwork']['showHints'] = 0;
                    $custom_claims['webwork']['showSolution'] = 0;
                    $custom_claims['webwork']['showDebug'] = 0;
                    $custom_claims['webwork']['showScoreSummary'] = $show_solutions;
                    $custom_claims['webwork']['showAnswerTable'] = $show_solutions;
                    $question['technology_iframe'] = '<iframe class="webwork_problem" frameborder=0 src="https://' . $webwork_domain . '/webwork2/html2xml?" width="100%"></iframe>';
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
     * @param string $json_type
     * @param string $student_response
     * @return false|string
     * @throws Exception
     */
    public
    function formatQtiJson(string $json_type, string $qti_json, $seed, bool $show_solution, string $student_response = '')
    {
        $qti_array = json_decode($qti_json, true);
        $question_type = $qti_array['questionType'];
        $domDocument = new DOMDocument();
        //fix images...there might be more of tese!
        foreach (['itemBody', 'prompt'] as $key) {
            if (isset($qti_array[$key])) {
                if ($key === 'itemBody') {
                    if (isset($qti_array['itemBody']['textEntryInteraction'])) {
                        $qti_array['itemBody']['textEntryInteraction'] = $this->addTimeToS3Images($qti_array['itemBody']['textEntryInteraction'], $domDocument, false);
                    } else {
                        $qti_array['itemBody'] = $this->addTimeToS3Images($qti_array['itemBody'], $domDocument, false);

                    }
                } else {
                    $qti_array[$key] = $this->addTimeToS3Images($qti_array[$key], $domDocument, false);
                }
            }
        }
        switch ($question_type) {
            case('highlight_table'):
                if ($student_response) {
                    $student_response = json_decode($student_response, 1);
                    foreach ($qti_array['rows'] as $row_key => $row) {
                        foreach ($row['responses'] as $response_key => $response) {
                            $qti_array['rows'][$row_key]['responses'][$response_key]['selected'] = in_array($response['identifier'], $student_response);
                        }
                    }
                }

                if (!$show_solution) {
                    foreach ($qti_array['rows'] as $row) {
                        unset($row['correctResponse']);
                    }
                    unset($qti_array['feedback']);
                } else {
                    if (!$student_response && $json_type === 'question_json') {
                        foreach ($qti_array['rows'] as $row_key => $row) {
                            foreach ($row['responses'] as $response_key => $response) {
                                unset($qti_array['rows'][$row_key]['responses'][$response_key]['correctResponse']);
                            }
                        }
                        if (request()->user()->role === 3) {
                            unset($qti_array['feedback']);
                        }
                    }
                    if ($json_type === 'answer_json') {
                        if (request()->user()->role === 3) {
                            unset($qti_array['feedback']);
                        }
                        foreach ($qti_array['rows'] as $row_key => $row) {
                            foreach ($row['responses'] as $response_key => $response) {
                                if ($qti_array['rows'][$row_key]['responses'][$response_key]['correctResponse']) {
                                    $qti_array['rows'][$row_key]['responses'][$response_key]['selected'] = true;
                                }
                            }
                        }
                    }
                }
                break;
            case('highlight_text'):
                if ($student_response) {
                    $student_response = json_decode($student_response, 1);
                    foreach ($qti_array['responses'] as $key => $response) {
                        $qti_array['responses'][$key]['selected'] = in_array($response['identifier'], $student_response);
                    }
                }
                if (!$show_solution) {
                    if (request()->user()->role === 3) {
                        foreach ($qti_array['responses'] as $key => $response) {
                            unset($qti_array['responses'][$key]['correctResponse']);
                        }
                        unset($qti_array['feedback']);
                    }
                } else {
                    if (!$student_response && $json_type === 'question_json') {
                        foreach ($qti_array['responses'] as $key => $response) {
                            unset($qti_array['responses'][$key]['correctResponse']);
                        }
                        if (request()->user()->role === 3) {
                            unset($qti_array['feedback']);
                        }
                    }
                    if ($json_type === 'answer_json') {
                        if (request()->user()->role === 3) {
                            unset($qti_array['feedback']);
                        }
                        foreach ($qti_array['responses'] as $key => $response) {
                            if ($response['correctResponse']) {
                                $qti_array['responses'][$key]['selected'] = true;
                            }
                        }
                    }
                }
                break;
            case('numerical'):
                if ($student_response) {
                    $student_response = json_decode($student_response, 1);
                    $margin_of_error = (float)$qti_array['correctResponse']['marginOfError'];
                    $diff = abs((float)$student_response - (float)$qti_array['correctResponse']['value']);

                    $qti_array['studentResponse'] = [
                        'response' => $student_response,
                        'answeredCorrectly' => $diff <= $margin_of_error
                    ];
                }
                if (!$show_solution) {
                    if (request()->user()->role === 3) {
                        unset($qti_array['correctResponse']);
                        unset($qti_array['feedback']);
                    }
                } else {
                    if (!$student_response && $json_type === 'question_json') {
                        if (request()->user()->role === 3) {
                            unset($qti_array['correctResponse']);
                            unset($qti_array['feedback']);
                        }
                    }
                    if ($json_type === 'answer_json') {
                        $qti_array['studentResponse'] = ['response' => $qti_array['correctResponse']['value']];
                    }
                }
                break;
            case('matrix_multiple_choice'):
                if ($student_response) {
                    $student_response = json_decode($student_response, 1);
                    foreach ($qti_array['rows'] as $key => $row) {
                        $qti_array['rows'][$key]['selected'] = $student_response[$key];
                    }
                }
                if (!$show_solution) {
                    foreach ($qti_array['rows'] as $row) {
                        unset($row['correctResponse']);
                    }
                    if (request()->user()->role === 3) {
                        unset($qti_array['feedback']);
                    }
                } else {
                    if (!$student_response && $json_type === 'question_json') {
                        foreach ($qti_array['rows'] as $key => $row) {
                            unset($qti_array['rows'][$key]['correctResponse']);
                        }
                        if (request()->user()->role === 3) {
                            unset($qti_array['feedback']);
                        }
                    }
                    if ($json_type === 'answer_json') {
                        if (request()->user()->role === 3) {
                            unset($qti_array['feedback']);
                        }
                        foreach ($qti_array['rows'] as $key => $row) {
                            $qti_array['rows'][$key]['selected'] = $qti_array['rows'][$key]['correctResponse'];
                        }
                    }
                }
                break;
            case('drag_and_drop_cloze'):
                $items_by_identifier = [];
                foreach (['correctResponses', 'distractors'] as $group) {
                    foreach ($qti_array[$group] as $item) {
                        $items_by_identifier[$item['identifier']] = $item['value'];
                    }
                }
                $select_options = [['value' => null, 'text' => 'Please choose an option']];
                if ($seed) {
                    $seed = json_decode($seed, 1);
                    foreach ($seed as $identifier) {
                        $select_options[] = ['value' => $identifier, 'text' => $items_by_identifier[$identifier]];
                    }
                } else {
                    //no seed for instructors
                    foreach ($qti_array['correctResponses'] as $response) {
                        $select_options[] = ['value' => $response['identifier'], 'text' => $response['value']];
                    }
                    foreach ($qti_array['distractors'] as $response) {
                        $select_options[] = ['value' => $response['identifier'], 'text' => $response['value']];
                    }
                }
                $qti_array['selectOptions'] = $select_options;
                if ($student_response) {
                    $qti_array['studentResponse'] = json_decode($student_response);
                }
                if (!$show_solution) {
                    if (request()->user()->role === 3) {
                        unset($qti_array['correctResponses']);
                        unset($qti_array['distractors']);
                        unset($qti_array['feedback']);
                    }
                } else {
                    if (!$student_response && $json_type === 'question_json') {
                        if (request()->user()->role === 3) {
                            unset($qti_array['correctResponses']);
                            unset($qti_array['distractors']);
                            unset($qti_array['feedback']);
                        }
                    }
                    if ($json_type === 'answer_json') {
                        unset($qti_array['feedback']);
                        foreach ($qti_array['correctResponses'] as $response) {
                            $qti_array['studentResponse'][] = $response['identifier'];
                        }
                    }
                }

                $qti_array['prompt'] = preg_replace('/\[(.*?)]/', '[select]', $qti_array['prompt']);
                break;
            case('drop_down_table'):
                foreach ($qti_array['rows'] as $row_key => $row) {
                    foreach ($row['responses'] as $value) {
                        $qti_array['rows'][$row_key]['selected'] = null;
                    }
                }
                if ($student_response) {
                    $student_response = json_decode($student_response, 1);
                    foreach ($qti_array['rows'] as $row_key => $row) {
                        foreach ($row['responses'] as $value) {
                            if (in_array($value['identifier'], $student_response)) {
                                $qti_array['rows'][$row_key]['selected'] = $value['identifier'];
                            }
                        }
                    }
                }
                if (!$show_solution) {
                    if (request()->user()->role === 3) {
                        foreach ($qti_array['rows'] as $row_key => $row) {
                            foreach ($row['responses'] as $response_key => $response) {
                                unset($qti_array['rows'][$row_key]['responses'][$response_key]['correctResponse']);
                            }
                        }
                        unset($qti_array['feedback']);
                    }
                } else {
                    if (!$student_response && $json_type === 'question_json') {
                        foreach ($qti_array['rows'] as $row_key => $row) {
                            foreach ($row['responses'] as $response_key => $response) {
                                unset($qti_array['rows'][$row_key]['responses'][$response_key]['correctResponse']);
                            }
                        }
                        if (request()->user()->role === 3) {
                            unset($qti_array['feedback']);
                        }
                    }
                    if ($json_type === 'answer_json') {
                        unset($qti_array['feedback']);
                        foreach ($qti_array['rows'] as $row_key => $row) {
                            foreach ($row['responses'] as $response_key => $response) {
                                if ($qti_array['rows'][$row_key]['responses'][$response_key]['correctResponse']) {
                                    $qti_array['rows'][$row_key]['selected'] = $response['identifier'];
                                }
                            }
                        }

                    }

                }
                if ($seed) {
                    $seed = json_decode($seed, 1);
                    foreach ($qti_array['rows'] as $row_key => $row) {
                        $header = $row['header'];
                        $responses_by_identifier = [];
                        $randomized_responses = [];
                        foreach ($row['responses'] as $value) {
                            $responses_by_identifier[$value['identifier']] = $value;
                        }
                        foreach ($seed[$header] as $identifier) {
                            $randomized_responses[] = $responses_by_identifier[$identifier];
                        }
                        $qti_array['rows'][$row_key]['responses'] = $randomized_responses;
                    }

                }
                break;
            case('multiple_response_grouping'):
                if ($student_response) {
                    $qti_array['studentResponse'] = json_decode($student_response, 1);
                }
                if (!$show_solution) {
                    if (request()->user()->role === 3) {
                        foreach ($qti_array['rows'] as $row_key => $row) {
                            foreach ($row['responses'] as $response_key => $value) {
                                unset($qti_array['rows'][$row_key]['responses'][$response_key]['correctResponse']);
                            }
                        }
                        unset($qti_array['feedback']);
                    }
                } else {
                    if (!$student_response && $json_type === 'question_json') {
                        foreach ($qti_array['rows'] as $row_key => $row) {
                            foreach ($row['responses'] as $response_key => $value) {
                                unset($qti_array['rows'][$row_key]['responses'][$response_key]['correctResponse']);
                            }
                        }
                        if (request()->user()->role === 3) {
                            unset($qti_array['feedback']);
                        }
                    }
                    if ($json_type === 'answer_json') {
                        $qti_array['studentResponse'] = [];
                        foreach ($qti_array['rows'] as $row) {
                            foreach ($row['responses'] as $response) {
                                if ($response['correctResponse']) {
                                    $qti_array['studentResponse'][] = $response['identifier'];
                                }
                            }
                        }


                    }
                }
                if ($seed) {
                    $seed = json_decode($seed, 1);
                    foreach ($qti_array['rows'] as $row_key => $row) {
                        $grouping = $row['grouping'];
                        $responses_by_identifier = [];
                        $randomized_responses = [];
                        foreach ($row['responses'] as $value) {
                            $responses_by_identifier[$value['identifier']] = $value;
                        }
                        foreach ($seed[$grouping] as $identifier) {
                            $randomized_responses[] = $responses_by_identifier[$identifier];
                        }
                        $qti_array['rows'][$row_key]['responses'] = $randomized_responses;
                    }
                }
                break;
            case('fill_in_the_blank'):
                if ($student_response) {
                    $qti_array['studentResponse'] = json_decode($student_response, 1);
                }

                if (!$show_solution) {
                    if (request()->user()->role === 3) {
                        unset($qti_array['responseDeclaration']['correctResponse']);
                    }
                } else {
                    if ($json_type === 'question_json') {
                        if (request()->user()->role === 3) {
                            if ($student_response) {
                                $submission = new Submission();
                                foreach ($qti_array['studentResponse'] as $key => $response) {
                                    $correct_response = (object)$qti_array['responseDeclaration']['correctResponse'][$key];
                                    $qti_array['studentResponse'][$key]['answeredCorrectly'] = $submission->correctFillInTheBlank($correct_response, $response['value']);
                                }
                            }
                            unset($qti_array['responseDeclaration']['correctResponse']);
                        }
                    }
                }
                if ($json_type === 'answer_json') {
                    $qti_array['studentResponse'] = [];
                    foreach ($qti_array['responseDeclaration']['correctResponse'] as $response) {
                        $qti_array['studentResponse'][] = ['value' => $response['value']];

                    }
                }
                break;
            case('multiple_response_select_n'):
            case('multiple_response_select_all_that_apply'):
                if ($student_response) {
                    $qti_array['studentResponse'] = json_decode($student_response, 1);
                }
                if (!$show_solution) {
                    if (request()->user()->role === 3) {
                        foreach ($qti_array['responses'] as $key => $response) {
                            unset($qti_array['responses'][$key]['correctResponse']);
                        }

                        unset($qti_array['feedback']);
                    }
                } else {
                    if (!$student_response && $json_type === 'question_json') {
                        foreach ($qti_array['responses'] as $key => $response) {
                            unset($qti_array['responses'][$key]['correctResponse']);
                        }
                        if (request()->user()->role === 3) {
                            unset($qti_array['feedback']);
                        }
                    }

                    if ($json_type === 'answer_json') {
                        if (request()->user()->role === 3) {
                            unset($qti_array['feedback']);
                        }
                        $qti_array['studentResponse'] = [];
                        foreach ($qti_array['responses'] as $response) {
                            if ($response['correctResponse']) {
                                $qti_array['studentResponse'][] = $response['identifier'];
                            }
                        }
                    }
                }
                //dd($qti_array);
                $responses_by_identifier = [];
                $randomzied_responses = [];
                foreach ($qti_array['responses'] as $response) {
                    $responses_by_identifier[$response['identifier']] = $response;
                }

                foreach ($qti_array['responses'] as $response) {
                    $randomzied_responses[] = $responses_by_identifier[$response['identifier']];
                }
                $qti_array['responses'] = $randomzied_responses;
                break;
            case('matrix_multiple_response'):
                if ($student_response) {
                    $qti_array['studentResponse'] = json_decode($student_response, 1);
                }
                if (!$show_solution) {
                    foreach ($qti_array['rows'] as $row) {
                        unset($row['correctResponse']);
                    }
                    if (request()->user()->role === 3) {
                        unset($qti_array['feedback']);
                    }
                } else {
                    if (!$student_response && $json_type === 'question_json') {
                        foreach ($qti_array['rows'] as $row) {
                            unset($row['correctResponse']);
                        }
                        if (request()->user()->role === 3) {
                            unset($qti_array['feedback']);
                        }
                    }
                    if ($json_type === 'answer_json') {
                        if (request()->user()->role === 3) {
                            unset($qti_array['feedback']);
                        }
                        $qti_array['studentResponse'] = [];
                        foreach ($qti_array['rows'] as $row) {
                            foreach ($row['responses'] as $response) {
                                if ($response['correctResponse']) {
                                    $qti_array['studentResponse'][] = $response['identifier'];
                                }
                            }
                        }
                    }
                }
                break;
            case('bow_tie'):
                if ($student_response) {
                    $qti_array['studentResponse'] = json_decode($student_response, 1);
                }

                if (!$show_solution) {
                    foreach (['actionsToTake', 'potentialConditions', 'parametersToMonitor'] as $group) {
                        foreach ($qti_array[$group] as $item) {
                            unset($item['correctResponse']);
                        }
                    }
                    if (request()->user()->role === 3) {
                        unset($qti_array['feedback']);
                    }
                } else {
                    if (!$student_response && $json_type === 'question_json') {
                        foreach (['actionsToTake', 'potentialConditions', 'parametersToMonitor'] as $group) {
                            foreach ($qti_array[$group] as $key => $item) {
                                unset($qti_array[$group][$key]['correctResponse']);
                            }
                        }
                        if (request()->user()->role === 3) {
                            unset($qti_array['feedback']);
                        }
                    }
                    if ($json_type === 'answer_json') {
                        if (request()->user()->role === 3) {
                            unset($qti_array['feedback']);
                        }
                        $qti_array['studentResponse'] = [];
                        foreach (['actionsToTake', 'potentialConditions', 'parametersToMonitor'] as $group) {
                            $qti_array['studentResponse'][$group] = [];
                            foreach ($qti_array[$group] as $value) {
                                if ($value['correctResponse']) {
                                    $qti_array['studentResponse'][$group][] = $value['identifier'];
                                }
                            }
                        }
                    }
                }
                $group_by_identifier = [];
                foreach (['actionsToTake', 'potentialConditions', 'parametersToMonitor'] as $group) {
                    $group_by_identifier[$group] = [];
                    foreach ($qti_array[$group] as $item) {
                        $group_by_identifier[$group][$item['identifier']] = $item;
                    }
                }
                if ($seed) {
                    $seed = json_decode($seed, 1);
                    foreach (['actionsToTake', 'potentialConditions', 'parametersToMonitor'] as $group) {
                        $randomized_groups = [];
                        foreach ($seed as $group => $identifiers) {
                            foreach ($identifiers as $identifier) {
                                $randomized_groups[$group][] = $group_by_identifier[$group][$identifier];
                            }
                        }
                    }
                    foreach (['actionsToTake', 'potentialConditions', 'parametersToMonitor'] as $group) {
                        $qti_array[$group] = $randomized_groups[$group];
                    }
                }
                break;
            case('drop_down_rationale');
            case('drop_down_rationale_triad');
            case('select_choice'):
                if ($student_response) {
                    $student_response = json_decode($student_response, 1);
                    $qti_array['studentResponse'] = $student_response;
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
                if (!$show_solution) {
                    if (request()->user()->role === 3) {
                        foreach ($qti_array['inline_choice_interactions'] as $identifier => $choices) {
                            foreach ($choices as $key => $value) {
                                unset($qti_array['inline_choice_interactions'][$identifier][$key]['correctResponse']);
                            }
                        }
                        unset($qti_array['feedback']);
                    }
                } else {
                    if ($json_type === 'question_json') {
                        if (!$student_response) {
                            if (request()->user()->role === 3) {
                                foreach ($qti_array['inline_choice_interactions'] as $identifier => $choices) {
                                    foreach ($choices as $key => $value) {
                                        unset($qti_array['inline_choice_interactions'][$identifier][$key]['correctResponse']);
                                    }
                                }
                                unset($qti_array['feedback']);
                            }
                        } else {
                            foreach ($student_response as $key => $response) {
                                foreach ($qti_array['inline_choice_interactions'] as $choices) {
                                    foreach ($choices as $choice) {
                                        if ($response['value'] === $choice['value']) {
                                            $student_response[$key]['answeredCorrectly'] = $choice['correctResponse'];
                                        }
                                    }
                                }
                            }
                            $qti_array['studentResponse'] = $student_response;
                        }
                    }
                }
                if ($json_type === 'answer_json') {
                    $student_response = [];
                    foreach ($qti_array['inline_choice_interactions'] as $identifier => $inline_choice_interactions) {
                        foreach ($inline_choice_interactions as $interaction) {
                            if ($interaction['correctResponse']) {
                                $student_response[] = [
                                    'identifier' => $identifier,
                                    'value' => $interaction['value']
                                ];
                            }
                        }
                    }
                    $qti_array['studentResponse'] = $student_response;
                }
                break;
            case('matching'):
                if ($student_response) {
                    $student_response = json_decode($student_response, 1);
                    $qti_array['studentResponse'] = $student_response;
                }
                foreach ($qti_array['possibleMatches'] as $key => $possible_match) {
                    $qti_array['possibleMatches'][$key]['matchingTerm'] = $this->addTimeToS3Images($possible_match['matchingTerm'], $domDocument, false);
                }
                foreach ($qti_array['termsToMatch'] as $key => $value) {
                    $qti_array['termsToMatch'][$key]['termToMatch'] = $this->addTimeToS3Images($value['termToMatch'], $domDocument, false);
                    $qti_array['feedback'][$key]['feedback'] = $this->addTimeToS3Images($value['feedback'], $domDocument, false);
                }

                if ($seed) {
                    $seeds = json_decode($seed, true);
                    $possible_matches_by_identifier = [];
                    $possible_matches = [];
                    foreach ($qti_array['possibleMatches'] as $possible_match) {
                        $possible_matches_by_identifier[$possible_match['identifier']] = $possible_match;
                    }
                    foreach ($seeds as $identifier) {
                        $possible_matches[] = $possible_matches_by_identifier[$identifier];
                    }

                    $qti_array['possibleMatches'] = $possible_matches;
                }


                if (!$show_solution) {
                    if (request()->user()->role === 3) {
                        foreach ($qti_array['termsToMatch'] as $key => $term_to_match) {
                            unset($qti_array['termsToMatch'][$key]['matchingTermIdentifier']);
                            unset($qti_array['termsToMatch'][$key]['feedback']);
                            unset($qti_array['feedback']);
                        }
                    }
                } else {
                    if ($json_type === 'question_json') {
                        if (request()->user()->role === 3) {
                            if ($student_response) {
                                foreach ($student_response as $key => $response) {
                                    foreach ($qti_array['termsToMatch'] as $term_to_match) {
                                        if ($term_to_match['identifier'] === $response['identifier']) {
                                            $student_response[$key]['answeredCorrectly'] = $term_to_match['matchingTermIdentifier'] === $response['chosenMatchIdentifier'];
                                        }
                                    }
                                }
                            } else {
                                foreach ($qti_array['termsToMatch'] as $key => $term_to_match) {
                                    unset($qti_array['termsToMatch'][$key]['feedback']);
                                    unset($qti_array['feedback']);
                                }
                            }
                            foreach ($qti_array['termsToMatch'] as $key => $term_to_match) {
                                unset($qti_array['termsToMatch'][$key]['matchingTermIdentifier']);
                            }
                        }
                        $qti_array['studentResponse'] = $student_response;

                    }
                    if ($json_type === 'answer_json') {
                        $student_response = [];
                        foreach ($qti_array['termsToMatch'] as $key => $term_to_match) {
                            unset($qti_array['termsToMatch'][$key]['feedback']);
                            unset($qti_array['feedback']);
                            $matching_term = '';
                            $matching_term_identifier = '';
                            foreach ($qti_array['possibleMatches'] as $possible_match) {
                                if ($possible_match['identifier'] === $term_to_match['matchingTermIdentifier']) {
                                    $matching_term = $possible_match['matchingTerm'];
                                    $matching_term_identifier = $possible_match['identifier'];
                                }
                            }
                            if (!$matching_term) {
                                throw new Exception ('Cannot find the matching term.');
                            }
                            if (!$matching_term_identifier) {
                                throw new Exception ('Cannot find the matching term identifier');
                            }
                            $student_response[] = ['identifier' => $term_to_match['identifier'],
                                'matchingTerm' => $matching_term,
                                'chosenMatchIdentifier' => $matching_term_identifier
                            ];
                        }
                        unset($qti_array['feedback']);
                        $qti_array['studentResponse'] = $student_response;

                    }

                }
                break;
            case('multiple_choice'):
            case('true_false'):
                if ($question_type === 'multiple_choice') {
                    $feedback_identifiers = ['correct', 'incorrect', 'any'];
                    foreach ($qti_array['simpleChoice'] as $simple_choice) {
                        $feedback_identifiers[] = $simple_choice['identifier'];
                    }
                    if (isset($qti_array['feedback'])) {
                        foreach ($feedback_identifiers as $identifier) {
                            if (isset($qti_array['feedback'][$identifier])) {
                                $qti_array['feedback'][$identifier] = $this->addTimeToS3Images($qti_array['feedback'][$identifier], $domDocument, false);
                            }
                        }
                    }
                }

                $simple_choices = $qti_array['simpleChoice'];

                if ($seed) {
                    $seeds = json_decode($seed, true);
                    $choices_by_identifier = [];
                    $simple_choices = [];
                    foreach ($qti_array['simpleChoice'] as $choice) {
                        $choices_by_identifier[$choice['identifier']] = $choice;
                    }
                    foreach ($seeds as $identifier) {
                        $simple_choices[] = $choices_by_identifier[$identifier];
                    }
                }
                $qti_array['simpleChoice'] = $simple_choices;
                if ($json_type === 'question_json') {
                    if ($student_response) {
                        $qti_array['studentResponse'] = $student_response;
                    }
                }
                if ($json_type === 'answer_json') {
                    $qti_array['studentResponse'] = '';
                }

                if (!$show_solution) {
                    if (request()->user()->role === 3) {
                        unset($qti_array['feedback']);
                    }
                } else {
                    if (!$student_response && $json_type === 'question_json') {
                        if (request()->user()->role === 3) {
                            unset($qti_array['feedback']);
                            foreach ($qti_array['simpleChoice'] as $key => $choice) {
                                unset($qti_array['simpleChoice'][$key]['correctResponse']);
                            }
                        }
                    }

                }
                foreach ($qti_array['simpleChoice'] as $key => $choice) {
                    unset($qti_array['simpleChoice'][$key]['editorShown']);
                    if (!$show_solution) {
                        if (request()->user()->role === 3) {
                            unset($qti_array['simpleChoice'][$key]['correctResponse']);
                            unset($qti_array['simpleChoice'][$key]['answeredCorrectly']);
                        }
                    } else {
                        if ($student_response && $json_type === 'question_json') {
                            if (request()->user()->role === 3) {
                                $identifier = $qti_array['simpleChoice'][$key]['identifier'];
                                if ($identifier !== $student_response) {
                                    unset($qti_array['simpleChoice'][$key]['correctResponse']);
                                    unset($qti_array['feedback'][$identifier]);
                                } else {
                                    if (isset($qti_array['simpleChoice'][$key]['correctResponse']) && $qti_array['simpleChoice'][$key]['correctResponse']) {
                                        unset($qti_array['feedback']['incorrect']);
                                    } else {
                                        unset($qti_array['feedback']['correct']);
                                    }
                                }
                            }
                        }
                    }

                    if ($json_type === 'answer_json') {
                        if (isset($qti_array['simpleChoice'][$key]['correctResponse'])
                            && $qti_array['simpleChoice'][$key]['correctResponse']) {
                            $qti_array['studentResponse'] = $qti_array['simpleChoice'][$key]['identifier'];

                        }
                    }

                }

                unset($qti_array['feedbackEditorShown']);

                break;
            case('multiple_answers'):
                $simple_choices = $qti_array['simpleChoice'];

                if ($seed) {
                    $seeds = json_decode($seed, true);
                    $choices_by_identifier = [];
                    $simple_choices = [];
                    foreach ($qti_array['simpleChoice'] as $choice) {
                        $choices_by_identifier[$choice['identifier']] = $choice;
                    }
                    foreach ($seeds as $identifier) {
                        $simple_choices[] = $choices_by_identifier[$identifier];
                    }
                }
                $qti_array['simpleChoice'] = $simple_choices;
                $student_response = json_decode($student_response, 1);
                $qti_array['studentResponse'] = $student_response;


                if ($json_type === 'answer_json') {
                    $qti_array['studentResponse'] = [];
                }

                if (!$student_response && $json_type === 'question_json') {
                    if (request()->user()->role === 3) {
                        foreach ($qti_array['simpleChoice'] as $key => $choice) {
                            unset($qti_array['simpleChoice'][$key]['correctResponse']);
                            unset($qti_array['simpleChoice'][$key]['feedback']);
                        }
                    }
                }

                foreach ($qti_array['simpleChoice'] as $key => $choice) {
                    unset($qti_array['simpleChoice'][$key]['editorShown']);
                    if (!$show_solution) {
                        if (request()->user()->role === 3) {
                            unset($qti_array['simpleChoice'][$key]['correctResponse']);
                            unset($qti_array['simpleChoice'][$key]['answeredCorrectly']);
                            unset($qti_array['simpleChoice'][$key]['feedback']);
                        }
                    } else {
                        if ($student_response && $json_type === 'question_json') {
                            if (request()->user()->role === 3) {
                                $identifier = $qti_array['simpleChoice'][$key]['identifier'];
                                $correct_response = $qti_array['simpleChoice'][$key]['correctResponse'] ?? false;
                                if ((in_array($identifier, $student_response) && $correct_response)
                                    || !in_array($identifier, $student_response) && !$correct_response) {
                                    unset($qti_array['simpleChoice'][$key]['feedback']);
                                }
                            }
                        }

                        if ($json_type === 'answer_json') {
                            if (isset($qti_array['simpleChoice'][$key]['correctResponse'])
                                && $qti_array['simpleChoice'][$key]['correctResponse']) {
                                $qti_array['studentResponse'][] = $qti_array['simpleChoice'][$key]['identifier'];
                            }

                        }
                    }
                }

                unset($qti_array['feedbackEditorShown']);
                break;
            default:
                throw new Exception("$question_type is not a valid question type.");

        }
        $qti_array['jsonType'] = $json_type;
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
                $webwork_domain = $this->getWebworkDomain();
                $endpoint = $this->getWebworkEndpoint();
                $technology_iframe = '<iframe allowtransparency="true" frameborder="0" src="' . $webwork_domain . '/' . $endpoint . '?answersSubmitted=0&sourceFilePath=' . $technology_id . '&problemSeed=1234567&showSummary=0&displayMode=MathJax&problemIdentifierPrefix=102&language=en&outputformat=libretexts&showScoreSummary=0&showAnswerTable=0" width="100%"></iframe>';
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
                $domain = $this->getWebworkDomain();
                $url = "$domain/render-api?answersSubmitted=0&sourceFilePath=$technology_id&problemSeed=1234567&showSummary=0&displayMode=MathJax&problemIdentifierPrefix=102&language=en&outputformat=libretexts&showScoreSummary=0&showAnswerTable=0";
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
                $tag = str_replace("'", '&apos;', $tag);
                $tag_in_db = DB::select("SELECT id FROM tags WHERE BINARY `tag`= convert('$tag' using utf8mb4) collate utf8mb4_bin LIMIT 1;");
                $tag_id = $tag_in_db
                    ? $tag_in_db[0]->id
                    : Tag::create(['tag' => $tag])->id;
                DB::table('question_tag')->insert(['question_id' => $this->id,
                    'tag_id' => $tag_id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()]);
            }
        }
    }

    function addFrameworkItems($framework_item_sync_question)
    {
        if ($framework_item_sync_question) {
            DB::table('framework_item_question')->where('question_id', $this->id)->delete();
            foreach (['level', 'descriptor'] as $type) {
                $types = "{$type}s";
                if ($framework_item_sync_question[$types]) {
                    foreach ($framework_item_sync_question[$types] as $item) {
                        if (!DB::table('framework_item_question')
                            ->where('question_id', $this->id)
                            ->where('framework_item_id', $item['id'])
                            ->where('framework_item_type', $type)
                            ->first()) {
                            $data = ['question_id' => $this->id,
                                'framework_item_id' => $item['id'],
                                'framework_item_type' => $type,
                                'created_at' => now(),
                                'updated_at' => now()];
                            DB::table('framework_item_question')->insert($data);
                        }
                    }
                }
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
        $source_url = null;
        $h5p_type = null;
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
            $h5p_type = $info['type'] ?? null;
            $source_url = $info['h5p_source'] ?: "https://studio.libretexts.org/h5p/$h5p_id";
            $body = $info['body'];
            $author = $this->getH5PAuthor($info);
            $license = $this->mapLicenseTextToValue($info['license']);
            $license_version = $license ? $info['license_version'] : null;
            $title = $this->getH5PTitle($info);
            $url = $this->getTechnologyURLFromTechnology('h5p', $h5p_id);
            $tags = $this->getH5PTags($info);
            $success = $info !== [];
        }
        return compact('h5p_type', 'author', 'license', 'license_version', 'title', 'url', 'source_url', 'tags', 'success', 'body');
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
                'CC PDM' => 'ccpdm',
                'CC BY' => 'ccby',
                'CC BY-NC' => 'ccbync',
                'CC BY-ND' => 'ccbynd',
                'CC BY-NC-ND' => 'ccbyncnd',
                'CC BY-NC-SA' => 'ccbyncsa',
                'CC BY-SA' => 'ccbysa',
                'GNU GPL' => 'gnu',
                'All Rights Reserved' => 'arr',
                'GNU FDL' => 'gnufdl',
                'NCBNS' => 'ncbsn'];
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
    function getQuestionIdsByPageId(int $page_id, string $library, bool $cache_busting): array
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
        $technology_id = null;
        try {
            if ($technology = $Libretext->getTechnologyFromBody($body)) {
                $technology_iframe = $Libretext->getTechnologyIframeFromBody($body, $technology);
                $technology_id = $this->getTechnologyIdFromTechnologyIframe($technology, $technology_iframe);
                $non_technology_html = str_replace($technology_iframe, '', $body);
                $has_non_technology = trim($non_technology_html) !== '';
            } else {
                $technology_iframe = '';
                $has_non_technology = true;
                $non_technology_html = str_replace($technology_iframe, '', $body);
                $technology = 'text';
            }

            $question_extras = $this->getQuestionExtras($dom,
                $Libretext,
                $technology_iframe,
                $page_id);

            $already_imported_from_libretext = DB::table('adapt_migrations')
                ->where('original_library', $library)
                ->where('original_page_id', $page_id)
                ->first();
            if (!$already_imported_from_libretext) {
                $already_imported_from_libretext = DB::table('adapt_mass_migrations')
                    ->where('original_library', $library)
                    ->where('original_page_id', $page_id)
                    ->first();
            }
            $title = $Libretext->getTitle($page_id);
            $saved_questions_folder = DB::table('saved_questions_folders')
                ->where('user_id', request()->user()->id)
                ->where('type', 'my_questions')
                ->where('name', 'Default')
                ->first();
            if (!$saved_questions_folder && app()->environment('testing')) {
                throw new Exception ("User needs a Default folder to import the questions.");
            }

            $question_data = ['technology' => $technology,
                'title' => $title,
                'library' => 'adapt',
                'non_technology' => $has_non_technology,
                'non_technology_html' => $has_non_technology ? $non_technology_html : null,
                'author' => $question_extras['author'],
                'license' => $question_extras['license'],
                'license_version' => $question_extras['license_version'],
                'technology_iframe' => $technology_iframe,
                'technology_id' => $technology_id,
                'text_question' => $text_question,
                'answer_html' => $answer_html,
                'solution_html' => $solution_html,
                'hint' => $hint,
                'libretexts_link' => $libretexts_link,
                'question_editor_user_id' => request()->user()->id,
                'folder_id' => $saved_questions_folder->id,
                'notes' => $technology === 'h5p' ? $question_extras['notes'] : $notes];
            if ($already_imported_from_libretext) {
                $question_id = $already_imported_from_libretext->new_page_id;
                $question = Question::find($question_id);
                Question::where('id', $question_id)
                    ->update($question_data);
            } else {
                $question_data['page_id'] = 0;
                $question = Question::create($question_data);
                DB::table('adapt_migrations')->insert([
                    'assignment_id' => 0,
                    'original_library' => $library,
                    'original_page_id' => $page_id,
                    'new_page_id' => $question->id,
                    'created_at' => now(),
                    'updated_at' => now()]);
            }
            $Libretext = new Libretext(['library' => $library]);
            $url = $Libretext->getUrl($page_id);
            $question->cached = !$cache_busting;
            $question->url = $url;
            $question->page_id = $question->id;
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

    public
    function reMigrateQuestion(Question $question, int $page_id, string $library): array
    {


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
        $technology_id = null;
        try {
            if ($technology = $Libretext->getTechnologyFromBody($body)) {
                $technology_iframe = $Libretext->getTechnologyIframeFromBody($body, $technology);
                $technology_id = $this->getTechnologyIdFromTechnologyIframe($technology, $technology_iframe);
                $non_technology_html = str_replace($technology_iframe, '', $body);
                $has_non_technology = trim($non_technology_html) !== '';
            } else {
                $technology_iframe = '';
                $has_non_technology = true;
                $non_technology_html = str_replace($technology_iframe, '', $body);
                $technology = 'text';
            }

            $question_extras = $this->getQuestionExtras($dom,
                $Libretext,
                $technology_iframe,
                $page_id);
            $Libretext = new Libretext(['library' => $library]);
            $title = $Libretext->getTitle($page_id);
            $url = $Libretext->getUrl($page_id);
            $question->update(
                ['technology' => $technology,
                    'non_technology' => $has_non_technology,
                    'non_technology_html' => $has_non_technology ? $non_technology_html : null,
                    'author' => $question_extras['author'],
                    'license' => $question_extras['license'],
                    'license_version' => $question_extras['license_version'],
                    'technology_iframe' => $technology_iframe,
                    'technology_id' => $technology_id,
                    'text_question' => $text_question,
                    'answer_html' => $answer_html,
                    'solution_html' => $solution_html,
                    'hint' => $hint,
                    'libretexts_link' => $libretexts_link,
                    'notes' => $technology === 'h5p' ? $question_extras['notes'] : $notes,
                    'cached' => 1,
                    'title' => $title,
                    'url' => $url]);


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

    function checkIfPageExists(Libretext $Libretext, int $page_id)
    {
        try {

            $response['type'] = 'error';
            $page_info = $Libretext->getPageInfoByPageId($page_id);
            $Libretext->getTechnologyAndTags($page_info);
            $contents = $Libretext->getContentsByPageId($page_id);
            $body = $contents['body'][0];

        } catch (Exception $e) {

            if (strpos($e->getMessage(), '403 Forbidden') === false) {
                //some other error besides forbidden
                $response['message'] = $e->getMessage();
                return $response;
            }
            //private page so try again!
            try {
                $contents = $Libretext->getPrivatePage('contents', $page_id);
                $body = $contents->body;
                $body = $body[0];
                if (!$body) {
                    $response['message'] = "This page has no HTML.";
                    return $response;
                }
                return $response;
            } catch (Exception $e) {
                $response['message'] = $e->getMessage();
                return $response;
            }
        }
        $response['type'] = 'success';
        return $response;
    }


    function getDomElementsFromBody($body)
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML($body);
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

        $question_id = $this->getQuestionIdsByPageId($page_id, $library, true)[0];//returned as an array
        $response['question_id'] = $question_id;
        $response['direct_import_id'] = "$library_text-$page_id";
        $response['type'] = 'success';

        return $response;
    }

    /**
     * @param Request $request
     * @param int $formative
     * @return array
     */
    public
    function getQuestionToAddByAdaptId(Request $request, int $formative): array
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
            if ($formative) {
                if (!DB::table('questions')->where('id', $question_id)->first()->question_editor_user_id !== $request->user()->id) {

                    $response['message'] = "You do not own $question_id so you cannot add it to this assignment which is part of a formative course.";
                    return $response;
                }
            }
        } else {
            $assignment_question = null;
            $question = Question::where('id', $assignment_question_arr[0])->where('version', 1)->first();
            if (!$question) {
                $response['message'] = "$assignment_question_arr[0] is not a valid Question ID.";
                return $response;
            } else {
                if ($formative) {
                    if ($question->question_editor_user_id !== $request->user()->id) {
                        $response['message'] = "You do not own $question->id so you cannot add it to this assignment which is part of a formative assignment.";
                        return $response;
                    }
                }
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
        @$dom->loadHTML($html);
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
     * @param Request $request
     * @param object $question_info
     * @return array
     * @throws Exception
     */
    public
    function formatQuestionFromDatabase(Request $request, object $question_info): array
    {
        $learning_outcome = DB::table('questions')
            ->join('question_learning_outcome', 'questions.id', '=', 'question_learning_outcome.question_id')
            ->join('learning_outcomes', 'question_learning_outcome.learning_outcome_id', '=', 'learning_outcomes.id')
            ->where('questions.id', $question_info['id'])
            ->select('subject')
            ->orderBy('question_learning_outcome.id', 'desc')
            ->first();
        $question_editor = DB::table('users')->where('id', $question_info['question_editor_user_id'])->first();
        if ($question_editor) {
            if ($question_editor->first_name === 'Default Non-Instructor Editor') {
                $question_editor_name = $question_editor->first_name;
            } else {
                $question_editor_name = "$question_editor->first_name $question_editor->last_name";
            }
        } else {
            $question_editor_name = 'No author name is available.';

        }
        $question['title'] = $question_info['title'];
        $question['subject'] = $learning_outcome ? $learning_outcome->subject : null;
        $question['id'] = $question_info['id'];
        $question['library'] = $question_info['library'];
        $question['license'] = $question_info['license'];
        $question['public'] = $question_info['public'];
        $question['page_id'] = $question_info['page_id'];
        $question['question_editor_user_id'] = $question_info['question_editor_user_id'];
        $question['question_editor_name'] = $question_editor_name;
        $question['iframe_id'] = $this->createIframeId();
        $question['technology'] = $question_info['technology'];
        $question['non_technology'] = $question_info['non_technology'];
        $question['webwork_code'] = $question_info['webwork_code'];
        $question['non_technology_iframe_src'] = $this->getHeaderHtmlIframeSrc($question_info);
        $question['technology_iframe'] = $question_info['technology_iframe'];
        $question['technology_iframe_src'] = $this->formatIframeSrc($question_info['technology_iframe'], $question['iframe_id']);
        $question['qti_json'] = $question_info['qti_json']
            ? $this->formatQtiJson('question_json', $question_info['qti_json'], [], false)
            : null;
        $question['qti_answer_json'] = $question_info['qti_json']
            ? $this->formatQtiJson('answer_json', $question_info['qti_json'], [], true)
            : null;

        if ($question_info['technology'] === 'webwork') {
            $custom_claims['iss'] = $request->getSchemeAndHttpHost();
            $custom_claims['aud'] = $this->getWebworkDomain();
            $custom_claims['webwork']['problemSeed'] = 1234;
            $custom_claims['webwork']['courseID'] = 'anonymous';
            $custom_claims['webwork']['userID'] = 'anonymous';
            $custom_claims['webwork']['course_password'] = 'anonymous';
            $custom_claims['webwork']['outputFormat'] = 'jwe_secure';
            $custom_claims['webwork']['hideAttemptsTable'] = 1;
            $custom_claims['webwork']['showSummary'] = 0;
            // $custom_claims['webwork']['answerOutputFormat'] = 'static';
            if (!$question['technology_iframe']) {
                {
                    dd('b');
                }
            } else {
                $technology_src = $this->getIframeSrcFromHtml(new DOMDocument(), $question['technology_iframe']);
                $custom_claims['webwork']['sourceFilePath'] = $this->getQueryParamFromSrc($technology_src, 'sourceFilePath');
                // $custom_claims['webwork']['sourceFilePath'] = $question_info['technology_id'];
            }

            $custom_claims['webwork']['problemUUID'] = rand(1, 1000);
            $custom_claims['webwork']['language'] = 'en';
            $custom_claims['webwork']['showHints'] = 0;
            $custom_claims['webwork']['showPreviewButton'] = 0;
            $custom_claims['webwork']['showSubmitButton'] = 0;
            $custom_claims['webwork']['showSolution'] = 0;
            $custom_claims['webwork']['showScoreSummary'] = 0;
            $custom_claims['webwork']['showAnswerTable'] = 0;
            $custom_claims['webwork']['showDebug'] = 0;
            $problemJWT = app()->environment('testing')
                ? 'someJWT'
                : $this->createProblemJWT(new JWE(), $custom_claims, 'webwork');
            $domain = $this->getWebworkDomain();
            $endpoint = $this->getWebworkEndpoint();
            $question['technology_iframe_src'] = "$domain/$endpoint?problemJWT=$problemJWT";

        }
        $question['text_question'] = $question_info['text_question'];
        $question['libretexts_link'] = $question_info['libretexts_link'];

        $question['notes'] = $question['answer_html'] = $question['solution_html'] = $question['hint'] = null;
        if (in_array(Auth::user()->role, [2, 5])) {
            $question['notes'] = $question_info['notes'];
            $question['answer_html'] = $this->addTimeToS3Images($question_info['answer_html'], new DOMDocument(), false);
            $question['solution_html'] = $this->addTimeToS3Images($question_info['solution_html'], new DOMDocument(), false);
            $question['hint'] = $question_info['hint'];
        }

        return $question;
    }

    public
    function getLikeQtiQuestions(string $questionType, $stripped_prompt, $question_id): Collection
    {

        $like_questions = DB::table('questions')
            ->where('qti_json', 'like', '%' . $stripped_prompt . '%')
            ->where('qti_json', 'like', '%"questionType":"' . $questionType . '"%');
        if ($question_id) {
            $like_questions = $like_questions->where('id', '<>', $question_id);
        }

        return $like_questions->get();
    }

    public
    function getMatchings($qti_json): array
    {
        $possible_matches_by_identifier = [];
        foreach ($qti_json['possibleMatches'] as $possible_match) {
            $possible_matches_by_identifier[$possible_match['identifier']] = $possible_match['matchingTerm'];
        }

        $matchings = [];

        foreach ($qti_json['termsToMatch'] as $value) {
            $matchings[] = [
                'term_to_match' => trim(strip_tags($value['termToMatch'])),
                'matching_term' => trim(strip_tags($possible_matches_by_identifier[$value['matchingTermIdentifier']]))
            ];
        }
        return $matchings;
    }

    public
    function qtiNumericalQuestionExists($qti_json, $prompt, $question_id): int
    {
        $stripped_prompt = trim(strip_tags($prompt));
        $like_questions = $this->getLikeQtiQuestions('numerical', $stripped_prompt, $question_id);
        $like_question_id = 0;
        foreach ($like_questions as $like_question) {
            $like_question_json = json_decode($like_question->qti_json, true);
            $stripped_like_prompt = trim(strip_tags($like_question_json['prompt']));
            if ($stripped_like_prompt === $stripped_prompt) {
                $like_question_id = $like_question->id;
            }
        }
        return $like_question_id;
    }

    public
    function qtiMatchingQuestionExists($question_type, $qti_json, $prompt, $question_id): int
    {
        $stripped_prompt = trim(strip_tags($prompt));
        $like_questions = $this->getLikeQtiQuestions($question_type, $stripped_prompt, $question_id);
        $qti_json = json_decode($qti_json, true);
        $matchings = $this->getMatchings($qti_json);
        $like_question_id = 0;
        foreach ($like_questions as $like_question) {
            $like_question_json = json_decode($like_question->qti_json, true);
            $stripped_like_prompt = trim(strip_tags($like_question_json['prompt']));
            $like_matchings = $this->getMatchings($like_question_json);
            if ($stripped_like_prompt === $stripped_prompt && $this->array_values_identical($matchings, $like_matchings)) {
                $like_question_id = $like_question->id;
            }
        }
        return $like_question_id;
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
        $qti_json = json_decode($qti_json, true);
        $like_questions = $this->getLikeQtiQuestions($qti_json['questionType'], $stripped_prompt, $question_id);

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
            if (!is_array($simple_choice['value'])) {
                $simple_choices[] = trim(strip_tags($simple_choice['value']));
            }
        }
        return $simple_choices;
    }

    public
    function getWebworkCodeFromFilePath($file_path)
    {
        $data = ['sourceFilePath' => $file_path];
        $endpoint = "https://wwrenderer.libretexts.org/render-api/tap";
        return $this->curlPost($endpoint, $data);

    }

    public
    function getWebworkHtmlFromCode($webwork_code)
    {
        $data = ['permissionLevel' => '20',
            'problemSeed' => '123',
            'outputFormat' => 'static',
            'problemSource' => base64_encode($webwork_code)];
        $endpoint = "https://wwrenderer.libretexts.org/render-api";
        return $this->curlPost($endpoint, $data);

    }

    /**
     * @param string $endpoint
     * @param array $data
     * @return bool|string
     */
    public
    function curlPost(string $endpoint, array $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_FAILONERROR, true); // Required for HTTP error codes to be reported via our call to curl_error($ch)

        $output = curl_exec($ch);
        $error_msg = curl_errno($ch) ? curl_error($ch) : '';
        curl_close($ch);

        if ($error_msg) {
            return $error_msg;
        }
        return $output;
    }

    /**
     * @param int $user_id
     * @param string $h5p_id
     * @param int $folder_id
     * @param int $assignment_id
     * @return array
     * @throws Exception
     */
    public
    function storeImportedH5P(int $user_id, string $h5p_id, int $folder_id, int $assignment_id = 0)
    {
        $data['library'] = 'adapt';
        if (!DB::table('saved_questions_folders')
            ->where('id', $folder_id)
            ->where('type', 'my_questions')
            ->where('user_id', $user_id)
            ->first()) {
            $response['message'] = "That is not one of your My Questions folders.";
            return $response;
        }
        $h5p_id = trim($h5p_id);
        if (!filter_var($h5p_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            $response['message'] = "$h5p_id should be a positive integer.";
        }
        $existing_question = Question::where('technology_id', $h5p_id)->where('technology', 'h5p')->first();

        $h5p = $this->getH5PInfo($h5p_id);
        if ($existing_question) {
            if (!$assignment_id) {
                $my_favorites_folder = DB::table('saved_questions_folders')
                    ->where('user_id', $user_id)
                    ->where('type', 'my_favorites')
                    ->orderBy('id')
                    ->first();
                if (!$my_favorites_folder) {
                    $saved_questions_folder = new SavedQuestionsFolder();
                    $saved_questions_folder->user_id = $user_id;
                    $saved_questions_folder->name = 'Main';
                    $saved_questions_folder->type = 'my_favorites';
                    $saved_questions_folder->save();
                    $my_favorites_folder_id = $saved_questions_folder->id;
                } else {
                    $my_favorites_folder_id = $my_favorites_folder->id;
                }

                $saved_questions_folder = DB::table('saved_questions_folders')
                    ->where('id', $my_favorites_folder_id)
                    ->first();
                if (DB::table('my_favorites')
                    ->where('user_id', $user_id)
                    ->where('question_id', $existing_question->id)
                    ->first()) {
                    $message = " (Already exists in ADAPT in your My Favorites folder '$saved_questions_folder->name')";
                } else {
                    $my_favorites = new MyFavorite();
                    $my_favorites->user_id = $user_id;
                    $my_favorites->folder_id = $my_favorites_folder_id;
                    $my_favorites->question_id = $existing_question->id;
                    $my_favorites->open_ended_submission_type = 0;
                    $my_favorites->save();
                    $message = " (Already exists in ADAPT, but added to your My Favorites folder '$saved_questions_folder->name')";
                }
                $h5p['title'] = $h5p['title'] . $message;
                $response['h5p'] = $h5p;
                $response['type'] = 'success';
                return $response;
            } else {
                $h5p['title'] = $h5p['title'] . ' (Already exists in ADAPT, just adding to assignment)';
            }
        }
        if (!$h5p['success']) {
            $response['h5p'] = $h5p;
            $response['message'] = "$h5p_id is not a valid id.";
            return $response;
        }
        if (!$existing_question) {
            $tags = $h5p['tags'];
            $data['question_type'] = 'assessment';
            $data['license'] = $h5p['license'];
            $data['author'] = $h5p['author'];
            $data['title'] = $h5p['title'];
            $data['h5p_type'] = $h5p['h5p_type'];
            $data['source_url'] = $h5p['source_url'];
            $data['h5p_owner_imported'] = 1;
            $data['notes'] = $h5p['body']
                ? '<div class="mt-section"><span id="Notes"></span><h2 class="editable">Notes</h2>' . $h5p['body'] . '</div>'
                : '';
            $data['technology'] = 'h5p';
            $data['license_version'] = $h5p['license_version'];
            $data['question_editor_user_id'] = $user_id;
            $data['url'] = null;
            $data['technology_id'] = $h5p_id;
            $data['technology_iframe'] = $this->getTechnologyIframeFromTechnology('h5p', $h5p_id);
            $data['non_technology'] = 0;
            $data['cached'] = true;
            $data['public'] = 0;
            $data['page_id'] = 1 + $this->where('library', 'adapt')->orderBy('page_id', 'desc')->value('page_id');
            $data['folder_id'] = $folder_id;
        }
        DB::beginTransaction();
        if (!$existing_question) {
            $question = Question::create($data);
            $question->page_id = $question->id;
            $question->save();
            Log::info(print_r($tags, 1));
            $question->addTags($tags);
        } else {
            $question = $existing_question;
        }

        if ($assignment_id) {
            $assignmentSyncQuestion = new AssignmentSyncQuestion();
            $assignment = Assignment::find($assignment_id);
            if (!in_array($question->id, $assignment->questions->pluck('id')->toArray())) {
                $assignmentSyncQuestion->store($assignment, $question, new BetaCourseApproval());
            }
        }
        $response['h5p'] = $h5p;
        $response['type'] = 'success';
        DB::commit();
        return $response;

    }

    /**
     * @param array $question_ids
     * @return Collection
     */
    public
    function getH5pNonAdapts(array $question_ids): Collection
    {
        return DB::table('questions')
            ->join('h5p_adapt_statuses', 'questions.h5p_type', '=', 'h5p_adapt_statuses.name')
            ->whereIn('questions.id', $question_ids)
            ->where('adapt_status', '<>', 'Ready')
            ->select('questions.id', 'questions.h5p_type')
            ->get();
    }

    public
    function getTechnologyIdFromTechnologyIFrame($technology, $technology_iframe)
    {
        preg_match('/src="([^"]+)"/', $technology_iframe, $match);
        if (!isset($match[1])) {
            return null;
        }
        $src = $match[1];
        switch ($technology) {
            case('webwork'):
                $src = str_replace('&amp;', '&', $src);
                $technology_id = $this->getQueryParamFromSrc($src, 'sourceFilePath');
                break;
            case('h5p'):
                preg_match('/(?<=h5p\/)(.*)(?=\/embed)/', $technology_iframe, $match);
                $technology_id = $match[1] ?? null;
                break;
            case('imathas'):
                $technology_id = $this->getQueryParamFromSrc($src, 'id');

        }

        return $technology_id;
    }

    public
    function getNasAndDollarSigns()
    {
        return $this->where('answer_html', 'LIKE', "%>N/A</p>%")
            ->orWhere('solution_html', 'LIKE', "%>N/A</p>%")
            ->orWhere('hint', 'LIKE', "%>N/A</p>%")
            ->orWhere('notes', 'LIKE', "%>N/A</p>%")
            ->orWhere('text_question', 'LIKE', "%>N/A</p>%")
            ->orWhere('answer_html', 'LIKE', "%>$</p>%")
            ->orWhere('solution_html', 'LIKE', "%<p>$</p>%")
            ->orWhere('hint', 'LIKE', "%<p>$</p>%")
            ->orWhere('notes', 'LIKE', "%<p>$</p>%")
            ->orWhere('text_question', 'LIKE', "%<p>$</p>%")
            ->select('id', 'answer_html', 'solution_html', 'hint', 'notes', 'text_question')
            ->get();
    }

    /**
     * @param User $user
     * @return bool
     */
    public
    function canBeViewedByStudent(User $user): bool
    {
        return Helper::isAnonymousUser() || DB::table('assignment_question')
                ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
                ->join('enrollments', 'assignments.course_id', '=', 'enrollments.course_id')
                ->where('enrollments.user_id', $user->id)
                ->where('questions.id', $this->id)
                ->select('questions.id')
                ->get()
                ->isNotEmpty();
    }

    /**
     * @param $question_ids
     * @return array
     */
    public
    function getQuestionTypes($question_ids): array
    {
        $question_types = [];
        $empty_learning_tree_nodes = DB::table('empty_learning_tree_nodes')
            ->select('question_id')
            ->whereIn('question_id', $question_ids)
            ->get('question_id')
            ->pluck('question_id')
            ->toArray();
        $questions = DB::table('questions')
            ->select('id', 'question_type')
            ->whereIn('id', $question_ids)
            ->get();
        foreach ($questions as $question) {
            $question_types[$question->id] = in_array($question->id, $empty_learning_tree_nodes)
                ? 'empty_learning_tree_node'
                : $question->question_type;
        }
        return $question_types;
    }

    /**
     * @return string
     */
    public
    function getWebworkEndpoint(): string
    {
        return 'render-api';

    }

    /**
     * @return string
     */
    public
    function getWebworkDomain(): string
    {
        return 'https://wwrenderer.libretexts.org';

    }

    /**
     * @return bool
     */
    private function _useS3Webwork(): bool
    {
        return $this->webwork_code && strpos($this->technology_id, 'private/ww_files/') === false;
    }

    /**
     * @return void
     */
    public function updateWebworkPath()
    {
        $dir = Helper::getWebworkCodePath();
        $this->technology_id = "{$dir}$this->id/code.pg";
        $this->technology_iframe = '<iframe class="webwork_problem" src="https://webwork.libretexts.org/webwork2/html2xml?answersSubmitted=0&sourceFilePath=' . $this->technology_id . '&problemSeed=1234567&showSummary=0&displayMode=MathJax&problemIdentifierPrefix=102&language=en&outputformat=libretexts" width="100%"></iframe>';
        $this->save();
    }


}

