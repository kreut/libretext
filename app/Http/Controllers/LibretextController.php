<?php


namespace App\Http\Controllers;

use App\Course;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Http\Requests\EmailSolutionErrorRequest;
use App\Libretext;
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


            if (!$question_id || !$assignment_id) {
                $response['message'] = "Need an assignment and question to migrate.";
                return $response;
            }
            $assignment_info = DB::table('assignment_question')
                ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->select('courses.id AS course_id',
                    'courses.name AS course_name',
                    'assignments.name AS assignment_name')
                ->where('assignment_question.assignment_id', $assignment_id)
                ->where('assignment_question.question_id', $question_id)
                ->first();
            $course = Course::find($assignment_info->course_id);
            if (!Helper::isCommonsCourse($course)) {
                $response['message'] = "This question doesn't come from a Commons course.";
                return $response;
            }

            $question = Question::find($question_id);
            if ($question->copy_source_id) {
                $response['question_message'] = "You cannot migrate a copy of a question.";
                return $response;
            }
            DB::beginTransaction();
            $question_message = 'Migrated';
            if ($question->library !== 'adapt') {
                $original_page_id = $question->page_id;
                $original_library = $question->library;
                $like_learning_trees = DB::table('learning_trees')
                    ->join('users', 'learning_trees.user_id', '=', 'users.id')
                    ->where('learning_tree', 'LIKE', "%$original_page_id%")
                    ->where('learning_tree', 'LIKE', "%$original_library%")
                    ->select('learning_tree', 'learning_trees.id', 'email')
                    ->get();


                $default_non_instructor_editor = DB::table('users')
                    ->where('email', 'Default Non-Instructor Editor has no email')
                    ->first();
                $folder_name = "$assignment_info->course_name --- $assignment_info->assignment_name";
                $saved_questions_folder = DB::table('saved_questions_folders')
                    ->where('user_id', $default_non_instructor_editor->id)
                    ->where('type', 'my_questions')
                    ->where('name', $folder_name)
                    ->first();
                if (!$saved_questions_folder) {
                    $savedQuestionFolder = new SavedQuestionsFolder();
                    $savedQuestionFolder->type = 'my_questions';
                    $savedQuestionFolder->name = $folder_name;
                    $savedQuestionFolder->user_id = $default_non_instructor_editor->id;
                    $savedQuestionFolder->save();
                    $saved_questions_folder_id = $savedQuestionFolder->id;
                } else {

                    $saved_questions_folder_id = $saved_questions_folder->id;
                }

                $Libretext = new Libretext(['library' => $question->library]);
                $response = $question->checkIfPageExists($Libretext, $question->page_id);

                $does_not_exist = $response['type'] === 'error' && isset($response['message']) && strpos($response['message'], '"status":"404"') !== false;
                if (!$does_not_exist) {
                    $question->getQuestionIdsByPageId($question->page_id, $question->library, 1);
                }

                $question->library = 'adapt';
                $question->page_id = $question->id;
                $question->question_editor_user_id = $default_non_instructor_editor->id;
                $question->folder_id = $saved_questions_folder_id;
                $question->save();


                DB::table('adapt_migrations')
                    ->insert(['original_library' => $original_library,
                        'assignment_id' => $assignment_id,
                        'original_page_id' => $original_page_id,
                        'new_page_id' => $question->id,
                        'created_at' => now(),
                        'updated_at' => now()]);
                if ($like_learning_trees) {
                    foreach ($like_learning_trees as $like_learning_tree) {

                        $learning_tree_object = json_decode($like_learning_tree->learning_tree, true);
                        $blocks = $learning_tree_object ['blocks'];
                        if (!$blocks) {
                            $response['question_message'] = "We were not able to convert the learning tree into JSON.";
                            return $response;
                        }
                        foreach ($blocks as $block_key => $block) {
                            $learning_tree_library = $learning_tree_page_id = '';
                            foreach ($block['data'] as $item) {
                                if ($item['name'] === 'library') {
                                    $learning_tree_library = $item['value'];
                                }
                                if ($item['name'] === 'page_id') {
                                    $learning_tree_page_id = $item['value'];
                                }
                            }

                            try {
                                if ($original_library === $learning_tree_library
                                    && (int)$original_page_id === (int)$learning_tree_page_id) {
                                    //update the blocks
                                    $is_root_node = $block['parent'] === -1;
                                    foreach ($block['data'] as $item_key => $item) {
                                        if ($item['name'] === 'library') {
                                            $blocks[$block_key]['data'][$item_key]['value'] = 'adapt';
                                        }
                                        if ($item['name'] === 'page_id') {
                                            $blocks[$block_key]['data'][$item_key]['value'] = $question->page_id;
                                        }
                                    }

                                    //update the html
                                    $input = "<input type='hidden' name='page_id' value='" . $original_page_id . "'>
        <input type='hidden' name='library' value='" . $original_library . "'>";
                                    $adapt_input = "<input type='hidden' name='page_id' value='" . $question->page_id . "'>
        <input type='hidden' name='library' value='adapt'>";
                                    $library_info = $this->getLibraryInfo($original_library);
                                    $original_library_color = $library_info['color'];
                                    $original_library_name = $library_info['name'];
                                    $header = "<img src='/assets/img/" . $original_library . ".svg' alt='" . $original_library . "' style='" . $original_library_color . "'><span class='library'>" . $original_library_name . "</span> - <span class='page_id'>" . $original_page_id . "</span>";
                                    $adapt_header = "<img src='/assets/img/adapt.svg' alt='adapt' style='blue'><span class='library'>ADAPT</span> - <span class='page_id'>" . $question->page_id . "</span>";

                                    $message = "Assignment: $assignment_id Question: $question_id In Learning Tree $like_learning_tree->id. Library: $learning_tree_library Page ID: $learning_tree_page_id User: $like_learning_tree->email ";
                                    $html = $learning_tree_object['html'];
                                    $html = str_replace("\\", '', $html);
                                    if (strpos($html, $input) === false) {
                                        $message = $message . "Could not find input $input" . $html;
                                        throw new Exception($message);
                                    } else {
                                        $html = str_replace($input, $adapt_input, $html);
                                    }

                                    if (strpos($like_learning_tree->learning_tree, $header) === false) {
                                        $message = $message . "Could not find input $header" . $html;
                                        throw new Exception($message);
                                    } else {
                                        $html = str_replace($header, $adapt_header, $html);
                                    }

                                    $learning_tree_object['html'] = $html;
                                    $learning_tree_object['blocks'] = $blocks;
                                    $learning_tree = json_encode($learning_tree_object);
                                    $learning_tree_owner = DB::table('learning_trees')
                                        ->join('users', 'learning_trees.user_id', '=', 'users.id')
                                        ->where('learning_trees.id', $like_learning_tree->id)
                                        ->first();

                                    DB::table('learning_tree_migrations')
                                        ->insert(['original_library' => $original_library,
                                            'original_page_id' => $original_page_id,
                                            'email' => $learning_tree_owner->email,
                                            'new_page_id' => $question->id,
                                            'learning_tree_id' => $like_learning_tree->id,
                                            'original_learning_tree' => $like_learning_tree->learning_tree,
                                            'migrated_learning_tree' => $learning_tree,
                                            'created_at' => now(),
                                            'updated_at' => now()]);

                                    $learning_tree_data = [
                                        'learning_tree' => $learning_tree,
                                        'updated_at' => now()
                                    ];

                                    if ($is_root_node) {
                                        $learning_tree_data['root_node_page_id'] = $question->page_id;
                                        $learning_tree_data['root_node_library'] = 'adapt';
                                    }
                                    DB::table('learning_trees')
                                        ->where('id', $like_learning_tree->id)
                                        ->update($learning_tree_data);

                                }
                            } catch (Exception $e) {
                                if (DB::transactionLevel()) {
                                    DB::rollback();
                                }
                                $h = new Handler(app());
                                $h->report($e);
                                $response['question_message'] = "Learning Tree issue; error logged with Eric";
                                return $response;
                            }
                        }
                    }
                }


            } else {
                $question_message = "Already migrated.";

            }
            $response['type'] = 'success';

            $response['question_message'] = $question_message;
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

    public function getLocallySavedPageContents(string $library, string $page_id)
    {
        return "<h2>Please refresh your page.  It looks like you are using an older version of ADAPT.</h2>";
    }

    /**
     * @param $question_id
     * @return Application|Factory|View
     * @throws Exception
     */
    public function getHeaderHtml($question_id)
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
                    $question = $question->where('id', $question_id)->first();
                    if ($question) {
                        $non_technology_html = $question->non_technology_html;
                    } else {
                        $non_technology_html = "We could not locate the Header HTML for Question $question_id";
                    }
                }
            }
        } catch (Exception $e) {
            $non_technology_html = "We were not able to retrieve the Header HTML for Question $question_id.  Please contact us for assistance.";
            $h = new Handler(app());
            $h->report($e);
        }

        $non_technology_html = trim($question->addTimeToS3Images($non_technology_html, new DOMDocument(), false));
        $non_technology_html = trim(str_replace(array("\n", "\r"), '', $non_technology_html));
        return view('header_html', ['non_technology_html' => $non_technology_html]);
    }


}
