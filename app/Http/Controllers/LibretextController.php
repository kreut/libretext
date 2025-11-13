<?php


namespace App\Http\Controllers;

use App\Course;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Http\Requests\EmailSolutionErrorRequest;
use App\Libretext;
use App\QuestionRevision;
use App\SavedQuestionsFolder;
use App\Traits\MindTouchTokens;
use DOMDocument;
use \Exception;
use App\Question;
use GuzzleHttp\Client;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Snowfire\Beautymail\Beautymail;

class LibretextController extends Controller
{

    use MindTouchTokens;


    /**
     * @param $library
     * @return string[]
     */
    public function getLibraryInfo($library): array
    {
        switch ($library) {
            case('adapt'):
                $name = 'ADAPT';
                $color = 'blue';
                break;
            case('bio'):
                $name = 'Biology';
                $color = '#00b224';
                break;
            case('chem'):
                $name = 'Chemistry';
                $color = 'rgb(0, 191, 255)';
                break;
            case('eng'):
                $name = 'English';
                $color = '#ff6a00';
                break;
            case('human'):
                $name = "Humanities";
                $color = '#00bc94';
                break;
            case('k12'):
                $name = "K12";
                $color = '#5cbf1c';
                break;
            case('law'):
                $name = "Law";
                $color = '#1c5d73';
                break;
            case('math'):
                $name = "Mathematics";
                $color = '#3737bf';
                break;
            case('med'):
                $name = "Medicine";
                $color = '#e52817';
                break;
            case('query'):
                $name = 'Query';
                $color = '#0060bc';
                break;
            case('phys'):
                $name = 'Physics';
                $color = '#841fcc';
                break;
            case('Social Science'):
                $name = 'SocialSci';
                $color = '#f20c92';
                break;
            case('stats'):
                $name = 'Statistics';
                $color = '#05baff';
                break;
            case('workforce'):
                $name = 'Workforce';
                $color = '#bf4000';
                break;
            default:
                $name = '';
                $color = '';
        }
        return ['name' => $name, 'color' => $color];
    }

    /**
     * @param EmailSolutionErrorRequest $request
     * @param Libretext $libretext
     * @return array
     * @throws Exception
     */
    public function emailSolutionError(EmailSolutionErrorRequest $request, Libretext $libretext): array
    {
        $response['type'] = 'error';
        try {
            $authorized = Gate::inspect('emailSolutionError', $libretext);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $data = $request->validated();
            $beauty_mail = app()->make(Beautymail::class);
            $question = DB::table('questions')->where('id', $request->question_id)->first();
            $url = "https://$question->library.libretexts.org/@go/page/$question->page_id";
            $error_info = ['text' => $data['text'],
                'instructor' => $request->user()->first_name . ' ' . $request->user()->last_name,
                'email' => $request->user()->email,
                'libretexts_id' => "$question->library-$question->page_id"];

            $beauty_mail->send('emails.solution_error', $error_info, function ($message)
            use ($request) {
                $message
                    ->from('adapt@noreply.libretexts.org', 'ADAPT')
                    ->to('delmar@libretexts.org')
                    ->replyTo($request->user()->email)
                    ->subject('Error in Solution');
            });
            $response['type'] = 'success';
            $response['message'] = 'Thank you for letting us know.  Someone will be in touch.';

        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            //don't need to tell them there was an error
        }
        return $response;


    }

    public function getLocallySavedPageContents(string $library, string $page_id)
    {
        return "<h2>Please refresh your page.  It looks like you are using an older version of ADAPT.</h2>";
    }

    /**
     * @param $question_id
     * @param int $revision_number
     * @return Application|Factory|View
     * @throws Exception
     */
    public function getHeaderHtml($question_id, int $revision_number = 0)
    {
        $question = new Question();

        try {
            $authorized = Gate::inspect('getHeaderHtml', [$question, $question_id]);
            if (!$authorized->allowed()) {
                $non_technology_html = $authorized->message();

            } else {
                if ($question_id === 'preview') {
                    $user_id = request()->user()->id;
                    $non_technology_html = Storage::disk('s3')->get("preview/$user_id.php");
                } else {
                    $questionRevision = new QuestionRevision();
                    $question_revision = $questionRevision->where('question_id', $question_id)->where('revision_number', $revision_number)->first();
                    $question = $question->where('id', $question_id)->first();
                    if (!$question) {
                        $non_technology_html = "We could not locate the Open-Ended Content for Question $question_id";
                    } else {
                        $non_technology_html = $question_revision ? $question_revision->non_technology_html : $question->non_technology_html;
                    }
                }
            }
        } catch (Exception $e) {
            $non_technology_html = "We were not able to retrieve the Open-Ended Content for Question $question_id.  Please contact us for assistance.";
            $h = new Handler(app());
            $h->report($e);
        }

        $non_technology_html = trim($question->addTimeToS3IFiles($non_technology_html, new DOMDocument(), false));
        $non_technology_html = trim(str_replace(array("\n", "\r"), '', $non_technology_html));

        return view('header_html', ['non_technology_html' => $non_technology_html]);
    }


}
