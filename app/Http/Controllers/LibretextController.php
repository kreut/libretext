<?php


namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Http\Requests\EmailSolutionErrorRequest;
use App\Libretext;
use DOMDocument;
use \Exception;
use App\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Snowfire\Beautymail\Beautymail;

class LibretextController extends Controller
{
    /**
     * @param Request $request
     * @param Libretext $libretext
     * @return array
     * @throws Exception
     */
    public function migrate(Request $request, Libretext $libretext): array
    {
        $response['type'] = 'error';
        $assignment_id = $request->assignment_id;
        $question_id = $request->question_id;

        try {

            $authorized = Gate::inspect('migrate', $libretext);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            if (!$assignment_id && !$question_id) {
                $response['message'] = "Neither an assignment nor question was chosen.";
                return $response;
            }

            $question_ids = $assignment_id ? DB::table('assignment_question')
                ->where('assignment_id', $assignment_id)
                ->get('question_id')
                ->pluck('question_id')
                : [$question_id];

            $questions = Question::whereIn('id', $question_ids);
            $questions = $questions->select('library', 'page_id', 'id', 'non_technology')->get();
            DB::beginTransaction();
            $in_learning_trees = [];
            $not_in_learning_trees = [];
            foreach ($questions as $question) {
                $in_learning_tree = false;
                $original_page_id = $question->page_id;
                $original_library = $question->library;

                $like_learning_trees = DB::table('learning_trees')
                    ->where('learning_tree', 'LIKE', "%$original_page_id%")
                    ->where('learning_tree', 'LIKE', "%$original_library%")
                    ->select('learning_tree')
                    ->get();

                if ($like_learning_trees) {
                    foreach ($like_learning_trees as $like_learning_tree) {
                        $blocks = json_decode($like_learning_tree->learning_tree, true)['blocks'];
                        if (!$blocks) {
                            $response['message'] = "We were not able to convert the learning tree into JSON.";
                            return $response;
                        }
                        foreach ($blocks as $block) {
                            $learning_tree_library = $learning_tree_page_id = '';
                            foreach ($block['data'] as $item) {
                                if ($item['name'] === 'library') {
                                    $learning_tree_library = $item['value'];
                                }
                                if ($item['name'] === 'page_id') {
                                    $learning_tree_page_id = $item['value'];
                                }
                            }
                            if ($original_library === $learning_tree_library
                                && (int)$original_page_id === (int)$learning_tree_page_id
                                && !in_array($question->id, $in_learning_trees)) {
                                $in_learning_tree = true;
                                $in_learning_trees[] = $question->id;
                            }
                        }
                    }
                }

                if (!$in_learning_tree) {
                    $not_in_learning_trees[] = $question->id;
                    $question->library = 'adapt';
                    $question->page_id = $question->id;
                    $question->save();
                    DB::table('adapt_migrations')
                        ->insert(['original_library' => $original_library,
                            'original_page_id' => $original_page_id,
                            'new_page_id' => $question->id,
                            'created_at' => now(),
                            'updated_at' => now()]);
                    if ((app()->environment() !== 'local') && $question->non_technology) {
                        if (!Storage::disk('s3')->exists("$original_library/$original_page_id.php")) {
                            $response['message'] = "We could not locate the file contents for Question ID $question->id.";
                            return $response;
                        }
                        $contents = Storage::disk('s3')->get("$original_library/$original_page_id.php");
                        if (Storage::disk('s3')->exists("adapt/$question->id.php")) {
                            $response['message'] = "There is already non-technology saved in ADAPT on S3 for Question ID $question->id.";
                            return $response;
                        }
                        Storage::disk('s3')->put("adapt/$question->id.php", $contents);
                    }
                }
            }
            if ($in_learning_trees) {
                if ($assignment_id) {
                    if ($not_in_learning_trees) {
                        $message = 'Migrated: ' . implode(', ', $not_in_learning_trees);
                        $message .= '<br><br>Not Migrated: ' . implode(', ', $in_learning_trees) . ' (in Learning Trees)';
                        $type = 'info';
                    } else {
                        $message = "Nothing was migrated since all questions are in Learning Trees.";
                        $type = 'error';
                    }
                } else {
                    $message = "The question was not migrated since it exists in a Learning Tree.";
                    $type = 'error';
                }
            } else {
                $message = $assignment_id
                    ? "All of the questions in the assignment have been migrated to ADAPT."
                    : "The question has been migrated to ADAPT.";
                $type = 'success';
            }
            $response['message'] = $message;
            $response['type'] = $type;
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to perform the migration.  Please try again or contact support for assistance.";
        }
        return $response;
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
                //  if (!$question_to_view->cached || !file_exists($file)) {
                $contents = Storage::disk('s3')->get("{$library}/{$pageId}.php");
                if ($is_efs) {
                    $contents = str_replace("require_once(__DIR__ . '/../libretext.config.php');",
                        'require_once("' . $efs_dir . 'libretext.config.php");', $contents);
                }
                //add MathJax to everything
                $contents = str_replace("'MathJax' => 0]", "'MathJax' => 1]", $contents);
//Create a new DOMDocument object.
                $contents = $question->addTimeToS3Images($contents, new DOMDocument);
                file_put_contents($file, $contents);
                Question::where('library', $library)->where('page_id', $pageId)->update(['cached' => 1]);
                //   }
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
