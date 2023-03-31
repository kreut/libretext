<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Question;
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

    public function getSrcDoc(Request $request, Assignment $assignment, Question $question, Submission $Submission)
    {
        try {
            $response['type'] = 'error';
            $url_components = parse_url($request->url);
            if (!$request->url) {
                $response['message'] = "You are missing a URL in your request.";
                return $response;
            }
            $submission = DB::table('submissions')
                ->where('user_id', $request->user()->id)
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();
            $submission_array =     $submission ? $Submission->getSubmissionArray($assignment, $question, $submission) : [];
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
