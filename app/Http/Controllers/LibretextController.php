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
            if ($question->clone_source_id) {
                $response['question_message'] = "You cannot migrate a copy of a question. (note to Delmar: not sure if this really true!  Shoot me an email)";
                return $response;
            }
            DB::beginTransaction();
            $question_message = 'Re-migrated';
            $adapt_migration_question = DB::table('adapt_migrations')->where('new_page_id', $question_id)->first();
            if ($adapt_migration_question) {
                $original_page_id = $adapt_migration_question->original_page_id;
                $original_library = $adapt_migration_question->original_library;
            } else {
                $adapt_mass_migration = DB::table('adapt_mass_migrations')->where('new_page_id', $question_id)->first();
                if ($adapt_mass_migration) {
                    $original_page_id = $adapt_mass_migration->original_page_id;
                    $original_library = $adapt_mass_migration->original_library;
                } else {
                    $response['question_message'] = "Could not find this question to be re-migrated.";
                    return $response;
                }
            }
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

            $Libretext = new Libretext(['library' => $original_library]);
            $response = $question->checkIfPageExists($Libretext, $original_page_id);
            $does_not_exist = $response['type'] === 'error' && isset($response['message']) && strpos($response['message'], '"status":"404"') !== false;
            if (!$does_not_exist) {
                $question->reMigrateQuestion($question, $original_page_id, $original_library, 1);
            }

            $question->question_editor_user_id = $default_non_instructor_editor->id;
            $question->folder_id = $saved_questions_folder_id;
            $question->save();

            if (!$adapt_migration_question) {
                DB::table('adapt_migrations')
                    ->insert(['original_library' => $original_library,
                        'assignment_id' => $assignment_id,
                        'original_page_id' => $original_page_id,
                        'new_page_id' => $question->id,
                        'created_at' => now(),
                        'updated_at' => now()]);
                DB::table('adapt_mass_migrations')->where('new_page_id', $question->id)->delete();
            } else {
                DB::table('adapt_migrations')
                    ->where('new_page_id', $question->id)
                    ->update(['assignment_id' => $assignment_id, 'updated_at' => now()]);
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

        $non_technology_html = trim($question->addTimeToS3Images($non_technology_html, new DOMDocument(), false));
        $non_technology_html = trim(str_replace(array("\n", "\r"), '', $non_technology_html));

        return view('header_html', ['non_technology_html' => $non_technology_html]);
    }


}
