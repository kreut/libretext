<?php


namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Http\Requests\EmailSolutionErrorRequest;
use App\Libretext;
use \Exception;
use App\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Snowfire\Beautymail\Beautymail;

class LibretextController extends Controller
{

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
                'url' => $url,
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

    /**
     * @param string $library
     * @param int $pageId
     * @param Question $question
     * @return array|string[]
     * @throws Exception
     */
    public function getLocallySavedPageContents(string $library, int $pageId, Question $question)
    {

        try {
            $authorized = Gate::inspect('viewByPageId', [$question, $library, $pageId]);
            if (!$authorized->allowed()) {
                if (\App::runningUnitTests()) {
                    return ['message' => $authorized->message()];
                }
                echo $authorized->message();
            } else {
                if (\App::runningUnitTests()) {
                    return ['message' => 'authorized'];
                }

                //if AWS, use EFS
                $efs_dir = '/mnt/local/';
                $is_efs = is_dir($efs_dir);
                $storage_path = $is_efs
                    ? $efs_dir
                    : Storage::disk('local')->getAdapter()->getPathPrefix();

                $file = "{$storage_path}{$library}/{$pageId}.php";
                if (!is_dir($storage_path . $library)) {
                    mkdir($storage_path . $library);
                }

                if ($is_efs && !file_exists("{$efs_dir}libretext.config.php")) {
                    file_put_contents("{$efs_dir}libretext.config.php", Storage::disk('s3')->get("libretext.config.php"));
                }

//if not cached or for some other reason it's not in the local file system...
                if ($library === 'preview') {
                    $question_to_view = new \stdClass();
                    $question_to_view->cached = false;
                } else {
                    $question_to_view = Question::where('library', $library)->where('page_id', $pageId)->first();
                }
                if (!$question_to_view->cached || !file_exists($file)) {
                    $contents = Storage::disk('s3')->get("{$library}/{$pageId}.php");
                    if ($is_efs) {
                        $contents = str_replace("require_once(__DIR__ . '/../libretext.config.php');",
                            'require_once("' . $efs_dir . 'libretext.config.php");', $contents);
                    }
                    file_put_contents($file, $contents);
                    Question::where('library', $library)->where('page_id', $pageId)->update(['cached' => 1]);
                }
                /**
                 * Original code to just grab from s3 everytime
                 * $contents = Storage::disk('s3')->get("{$library}/{$pageId}.php");
                 * if ($is_efs) {
                 * if (!file_exists("{$efs_dir}libretext.config.php")) {
                 * file_put_contents("{$efs_dir}libretext.config.php", Storage::disk('s3')->get("libretext.config.php"));
                 * }
                 * $contents = str_replace("require_once(__DIR__ . '/../libretext.config.php');",
                 * 'require_once("' . $efs_dir . 'libretext.config.php");', $contents);
                 * }
                 * file_put_contents($file, $contents);
                 ***/


                require_once($file);

            }
        } catch (Exception $e) {
            echo "We were not able to retrieve Page Id $pageId from the $library library.  Please contact us for assistance.";
            $h = new Handler(app());
            $h->report($e);
        }
    }
}
