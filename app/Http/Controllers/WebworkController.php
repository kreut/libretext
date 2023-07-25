<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Question;
use App\SavedQuestionsFolder;
use App\Submission;
use App\Webwork;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class WebworkController extends Controller
{

    public function list(Webwork $webwork)
    {
        //for testing
        if (app()->environment() !== 'local') {
            dd('no access');
        }


    }


    public function templates(Question $question, Webwork $webwork): array
    {
        $response['type'] = 'error';
        try {
            $authorized = Gate::inspect('templates', $webwork);

            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $response['webwork_templates'] = $question->where('folder_id', 3346)->get();
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the webwork templates.  Please try again or contact us for assistance";
        }
        return $response;

    }

    /**
     * @return string
     */
    private function _errorPage($error): string
    {
        return <<<DOC
        <link href="https://wwrenderer.libretexts.org/typing-sim.css" rel="stylesheet">
<link href="https://wwrenderer.libretexts.org/crt-display.css" rel="stylesheet">
<script>//<![CDATA[

    window.onload = function() {
        var i = 0;
        var tag = document.getElementById('error-block');
        var text = tag.getAttribute('text');
        var speed = 150;

        function typeWriter() {
            if (i <= text.length) {
                i++;
                tag.innerHTML = text.slice(0 ,i);
                setTimeout(typeWriter, speed);
            }
        }

        typeWriter();
    }

//]]></script>
<body class="crt">
    <div class="typewriter">
        <h1 id="error-block" text="$error">></h1>
    </div>
</body>
DOC;

    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param Submission $Submission
     * @return array
     * @throws Exception
     */
    public function getSrcDoc(Request    $request,
                              Assignment $assignment,
                              Question   $question,
                              Submission $Submission): array
    {
        try {
            $response['type'] = 'error';
            $url_components = parse_url($request->url);
            if (!$request->url) {
                $response['message'] = "You are missing a URL in your request.";
                return $response;
            }
            switch ($request->table) {
                case ('submissions'):
                    $submission = DB::table('submissions')
                        ->where('user_id', $request->user()->id)
                        ->where('assignment_id', $assignment->id)
                        ->where('question_id', $question->id)
                        ->first();
                    break;
                case('learning_tree_node_submissions'):
                    $submission = DB::table('learning_tree_node_submissions')
                        ->where('user_id', $request->user()->id)
                        ->where('learning_tree_id', $request->learning_tree_id)
                        ->where('assignment_id', $assignment->id)
                        ->where('question_id', $question->id)
                        ->first();
                    break;
                default:
                    throw new Exception("$request->table is not a valid submission table");
            }

            $submission_array = $submission ? $Submission->getSubmissionArray($assignment, $question, $submission, $request->table === 'learning_tree_node_submissions') : [];
            parse_str($url_components['query'], $params);
            if (!isset($params['sessionJWT'])) {
                $params['sessionJWT'] = '';
            }
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $request->url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST"
            ));

            $response['src_doc'] = curl_exec($curl);
            if (curl_errno($curl)) {
                $response['src_doc'] = curl_error($curl);
            } else {
                $response['type'] = 'success';
                $response['submission_array'] = $submission_array;
            }
            curl_close($curl);
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving this webwork question.  Please try again or contact us for assistance";
        }
        return $response;

    }

    public function delete(Webwork $webwork)
    {
        //for testing
        if (app()->environment() !== 'local') {
            dd('no access');
        }
        $webwork_path = Helper::getWebworkCodePath();
        try {
            $webwork->deletePath($webwork_path . '152906/some_file.pg');
        } catch (Exception $e) {
            echo $e->getMessage();
        }


    }

    /**
     * @param Webwork $webwork
     * @return bool|string
     */
    public function cloneDir(Webwork $webwork)
    {
        //for testing
        if (app()->environment() !== 'local') {
            dd('no access');
        }


    }

    public function convertDefFileToMassWebworkUploadCSV(Request $request)
    {
        $contents = file($request->file);
        foreach ($contents as $line) {
            if (str_starts_with($line, 'source_file')) {
                $pg_file = str_replace('source_file = ', '', $line);
                echo $pg_file;
            }
        }
        exit;
        $contents = file_get_contents('/Users/franciscaparedes/Downloads/setCobleBigIdeasCosmology5.def');
        dd($contents);

    }
}
