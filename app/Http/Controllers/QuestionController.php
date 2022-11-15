<?php

namespace App\Http\Controllers;


use App\Assignment;
use App\AssignmentSyncQuestion;
use App\AssignmentTemplate;
use App\AssignmentTopic;
use App\BetaCourseApproval;
use App\Course;
use App\Helpers\Helper;
use App\Http\Requests\StoreQuestionRequest;
use App\Jobs\ProcessValidateQtiFile;
use App\JWE;
use App\LearningTree;
use App\Libretext;
use App\MyFavorite;
use App\QtiJob;
use App\Question;
use App\SavedQuestionsFolder;
use App\Section;
use App\Tag;
use App\Traits\AssignmentProperties;
use App\Traits\DateFormatter;
use App\RefreshQuestionRequest;
use App\User;
use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use App\Solution;
use App\Traits\IframeFormatter;
use App\Traits\LibretextFiles;
use App\Exceptions\Handler;
use \Exception;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use Symfony\Component\HttpFoundation\StreamedResponse;


class QuestionController extends Controller
{
    use IframeFormatter;
    use LibretextFiles;
    use DateFormatter;
    use AssignmentProperties;

    /**
     * @var string[]
     */
    private $webwork_keys;
    /**
     * @var string[]
     */
    private $advanced_keys;

    public function __construct()
    {
        $this->webwork_keys = ['Public*',
            'Folder*',
            'Title*',
            'File Path*',
            'Assignment',
            'Template',
            'Topic',
            'Author*',
            'License*',
            'License Version',
            'Source URL',
            'Tags'];
        $this->advanced_keys = ["Question Type*",
            "Public*",
            'Folder*',
            "Title*",
            'Assignment',
            "Template",
            'Topic',
            "Open-Ended Content",
            "Auto-Graded Technology",
            "Technology ID/File Path",
            "Author*",
            "License*",
            "License Version",
            "Source URL",
            "Tags",
            "Text Question",
            "Answer",
            "Solution",
            "Hint"
        ];

    }

    /**
     * @param Request $request
     * @param Question $question
     * @return array
     * @throws Exception
     */
    public function getQuestionTypes(Request $request, Question $question): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getQuestionTypes', $question);
        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['question_types'] = $question->getQuestionTypes($request->question_ids);
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to get the question types.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function getQtiAnswerJson(Request $request, Question $question)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getQtiAnswerJson', $question);
        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            if (!json_decode($request->qti_json)) {
                $response['message'] = "The answer cannot be viewed.  Please verify that the all parts to the question have been created.";
                return $response;
            }
            $response['qti_answer_json'] = $question->formatQtiJson('answer_json', $request->qti_json, [], true);
            $response['type'] = 'success';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the answer.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param Question $question
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param BetaCourseApproval $betaCourseApproval
     * @return array
     * @throws Exception
     */
    public function clone(Request          $request,
                          Question               $question,
                          AssignmentSyncQuestion $assignmentSyncQuestion,
                          BetaCourseApproval     $betaCourseApproval): array
    {
        $response['type'] = 'error';
        $cloned_question = [];
        $question_id = $request->question_id;
        $clone_source = Question::find($question_id);
        if (!$clone_source) {
            $response['message'] = "Question $question_id does not exist.";
            return $response;
        }

        try {
            $authorized = Gate::inspect('clone', $clone_source);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }

            $question_editor_user_id = $request->question_editor_user_id;

            $assignment_id = $request->assignment_id;
            $assignment = null;
            $acting_as = $request->acting_as;
            $clone_to_folder_id = $request->clone_to_folder_id;

            if ($acting_as === 'admin' && !request()->user()->isMe()) {
                $response['message'] = 'You are not Admin.';
                return $response;
            }
            $question_editor = User::find($question_editor_user_id);
            switch ($acting_as) {
                case('admin'):
                    if (!in_array($question_editor->role, [2, 5])) {
                        $response['message'] = "The new owner must be an instructor or non-instructor editor.";
                        return $response;
                    }

                    $saved_questions_folder = DB::table('saved_questions_folders')
                        ->where('user_id', $question_editor_user_id)
                        ->where('type', 'my_questions')
                        ->where('name', 'Cloned questions')
                        ->first();
                    if (!$saved_questions_folder) {
                        $savedQuestionFolder = new SavedQuestionsFolder();
                        $savedQuestionFolder->type = 'my_questions';
                        $savedQuestionFolder->name = 'Cloned questions';
                        $savedQuestionFolder->user_id = $question_editor_user_id;
                        $savedQuestionFolder->save();
                        $saved_questions_folder_id = $savedQuestionFolder->id;
                    } else {
                        $saved_questions_folder_id = $saved_questions_folder->id;
                    }

                    $clone_to_folder_id = $saved_questions_folder_id;
                    break;
                default:
                    if ($question_editor_user_id !== request()->user()->id) {
                        $response['message'] = "You cannot clone a question to someone else's account.";
                        return $response;
                    }
                    if ($assignment_id) {
                        $assignment = Assignment::find($assignment_id);
                        if (!$assignment) {
                            $response['message'] = "That assignment does not exist.";
                            return $response;
                        }
                        $assignment_owner_user_id = $assignment->course->user_id;
                        if ($assignment_owner_user_id !== request()->user()->id) {
                            $response['message'] = "You cannot clone the question to an assignment that you don't own.";
                            return $response;
                        }
                        $assignment = Assignment::find($assignment_id);
                    }

                    if (!$clone_to_folder_id) {
                        $response['message'] = "Please choose a folder.";
                        return $response;
                    }
                    $saved_questions_folder = DB::table('saved_questions_folders')
                        ->where('id', $clone_to_folder_id)
                        ->where('type', 'my_questions')
                        ->first();
                    if (!$saved_questions_folder) {
                        $response['message'] = "That folder does not exist.";
                        return $response;
                    } else if ($saved_questions_folder->user_id !== request()->user()->id) {
                        $response['message'] = "You do not own that folder.";
                        return $response;
                    }
            }

            DB::beginTransaction();
            $cloned_question = $clone_source->replicate();
            $cloned_question->clone_source_id = $question_id;
            $cloned_question->question_editor_user_id = $question_editor_user_id;
            $cloned_question->folder_id = $clone_to_folder_id;
            $cloned_question->save();
            $cloned_question->page_id = $cloned_question->id;
            $cloned_question->save();
            if ($assignment_id) {
                $assignmentSyncQuestion->addQuestiontoAssignmentByQuestionId($assignment,
                    $cloned_question->id,
                    $assignmentSyncQuestion,
                    $assignment->default_open_ended_submission_type,
                    $assignment->default_open_ended_text_editor,
                    $betaCourseApproval);

            }
            $learning_outcomes = DB::table('question_learning_outcome')
                ->where('question_id', $question_id)
                ->get();
            foreach ($learning_outcomes as $learning_outcome) {
                DB::table('question_learning_outcome')
                    ->insert(['question_id' => $cloned_question->id,
                        'learning_outcome_id' => $learning_outcome->id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()]);
            }
            $tags = DB::table('question_tag')
                ->where('question_id', $question_id)
                ->get();

            foreach ($tags as $tag) {
                DB::table('question_tag')
                    ->insert(['question_id' => $cloned_question->id,
                        'tag_id' => $tag->tag_id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()]);
            }
            DB::commit();
            if ($acting_as === 'admin') {
                $user = User::find($question_editor_user_id);
                $message = "$clone_source->title has been cloned and $user->first_name $user->last_name has been given editing rights.";
            } else {
                $clone_to_folder = SavedQuestionsFolder::find($clone_to_folder_id);
                $message = "The question has been cloned to your '$clone_to_folder->name' folder.";
                if ($assignment_id) {
                    $message .= "<br><br>In addition, it has been added to $assignment->name.";
                }
            }
            $response['message'] = $message;
            $response['type'] = 'success';

        } catch
        (Exception $e) {
            DB::rollback();
            if ($cloned_question && Storage::disk('s3')->exists("adapt/$cloned_question->id.php")) {
                Storage::disk('s3')->delete("adapt/$cloned_question->id.php");
            }
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error cloning the question.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Question $question
     * @return array
     * @throws Exception
     */
    public function getAssignmentStatus(Question $question): array
    {
        $response['type'] = 'error';
        try {
            $response['question_exists_in_own_assignment'] = $question->questionExistsInOneOfTheirAssignments();
            $response['question_exists_in_another_instructors_assignment'] = $question->questionExistsInAnotherInstructorsAssignments();
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error seeing if this exists in another instructor's assignment.  Please try again or contact us for assistance.";
        }


        return $response;


    }


    /**
     * @return array
     */
    public
    function getValidLicenses(): array
    {
        $response['licenses'] = ['publicdomain', 'ccby', 'ccbynd', 'ccbync', 'ccbyncnd', 'ccbyncsa', 'ccbysa', 'gnu', 'arr', 'gnufdl', 'imathascomm', 'ck12foundation'];
        return $response;
    }

    /**
     * @param Request $request
     * @param Question $question
     * @param Section $section
     * @return array
     * @throws Exception
     */
    public
    function validateBulkImportQuestions(Request  $request,
                                         Question $question,
                                         Section  $section): array
    {
        $import_template = $request->import_template;
        $course_id = $request->course_id;
        $response['type'] = 'error';

        $authorized = Gate::inspect('validateBulkImportQuestions', [$question, $course_id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response = $import_template === 'qti' ?
                $this->validateQTIBulkImport($request)
                : $this->validateCSVBulkImport($request, $import_template, $course_id, $section);
            if ($response['message']) {
                if (DB::transactionLevel()) {
                    DB::rollback();
                }
                return $response;
            }
            $response['type'] = 'success';
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $message = "We were not able to upload the questions file.  Please try again or contact us for assistance.";
            $response['message'] = $import_template === 'qti' ? $message : [$message];
        }
        return $response;

    }

    /**
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public function validateQTIBulkImport(Request $request): array
    {
        $savedQuestionsFolder = new SavedQuestionsFolder();
        $response['message'] = [];
        $author_error = '';
        $folder_error = '';
        $license_error = '';
        $import_to_course_error = '';
        $assignment_template_error = '';
        if ($request->import_to_course) {
            $course = Course::find($request->import_to_course);
            if (!$course || $request->user()->id !== $course->user_id) {
                $import_to_course_error = "That is not one of your courses.";
            } else {
                if ($message = $course->bulkUploadAllowed()) {
                    $import_to_course_error = $message;
                } else {
                    if (!$request->assignment_template) {
                        $assignment_template_error = "Please choose an assignment template.";
                    } else {
                        $assignment_template = DB::table('assignment_templates')
                            ->where('id', $request->assignment_template)
                            ->where('user_id', $request->user()->id)
                            ->first();
                        if (!$assignment_template) {
                            $assignment_template_error = "That is not one of your assignment templates.";
                        }
                    }
                }
            }

        }
        if (!$request->author) {
            $author_error = "An author is required.";
        }
        if (!$request->folder_id) {
            $folder_error = "Please select a folder.";
        } else if (!$savedQuestionsFolder->isOwner($request->folder_id)) {
            $folder_error = "That is not one of your folders.";
        }
        if (!$request->license) {
            $license_error = "A license is required.";
        }
        if ($author_error || $folder_error || $license_error || $import_to_course_error || $assignment_template_error) {
            $response['message']['form_errors'] = ['author' => $author_error,
                'folder_id' => $folder_error,
                'license' => $license_error,
                'import_to_course' => $import_to_course_error,
                'assignment_template' => $assignment_template_error
            ];
            return $response;
        }

        $qtiJob = new QtiJob();
        $qtiJob->user_id = $request->user()->id;
        $qtiJob->qti_directory = pathinfo($request->qti_file)['filename'];
        $qtiJob->qti_source = $request->qti_source;
        $qtiJob->public = $request->public;
        $qtiJob->course_id = $request->import_to_course;
        $qtiJob->assignment_template_id = $request->assignment_template;
        $qtiJob->folder_id = $request->folder_id;
        $qtiJob->license = $request->license;
        $qtiJob->license_version = $request->license_version;
        $qtiJob->source_url = $request->source_url;
        $qtiJob->status = 'processing';
        $qtiJob->save();
        if (!app()->environment('testing')) {
            ProcessValidateQtiFile::dispatch($qtiJob, $request->qti_file, $request->import_to_course, $request->assignment_template);
        }
        $response['message'] = null;
        $response['qti_job_id'] = $qtiJob->id;
        return $response;
    }

    /**
     * @param Request $request
     * @param string $import_template
     * @param $course_id
     * @param $section
     * @return array
     */
    public function validateCSVBulkImport(Request $request,
                                          string  $import_template,
                                                  $course_id,
                                                  $section): array
    {

        if (!app()->environment('testing')) {
            if (!$request->file('bulk_import_questions_file')) {
                $response['message'] = ['No file was selected.'];
                return $response;
            }
            $bulk_import_questions_file = $request->file('bulk_import_questions_file')->store("override-scores/" . Auth()->user()->id, 'local');
            $csv_file = Storage::disk('local')->path($bulk_import_questions_file);

            if (!in_array($request->file('bulk_import_questions_file')->getMimetype(), ['application/x-tex', 'application/csv', 'text/plain', 'text/x-tex'])) {
                $response['message'] = ["This is not a .csv file: {$request->file('bulk_import_questions_file')->getMimetype()} is not a valid MIME type."];
                return $response;
            }
            $handle = fopen($csv_file, 'r');
            $header = fgetcsv($handle);
            Log::info($import_template);

            $correct_keys = $import_template === 'webwork' ? $this->webwork_keys : $this->advanced_keys;
            if ($course_id) {
                if (count($header) < count($correct_keys)) {
                    Log::info(print_r($header, true));
                    Log::info(print_r($correct_keys, true));
                    $response['message'] = ["It looks like you are trying to import your .csv file into a course but the .csv file is missing some of the course items in the header."];
                    return $response;
                }
            } else {
                if (count($header) > count($this->_removeCourseInfo($correct_keys))) {
                    Log::info(print_r($header, true));
                    Log::info(print_r($this->_removeCourseInfo($correct_keys), true));
                    $response['message'] = ["It looks like you are trying to import your .csv file outside of a course but the .csv file has course items in the header."];
                    return $response;
                }
            }
            fclose($handle);
            $bulk_import_questions = Helper::csvToArray($csv_file);
        } else {
            $bulk_import_questions = $request->csv_file_array;

        }

        if (!$bulk_import_questions) {
            $response['message'] = ['The .csv file has no data.'];
            return $response;
        }
        switch ($import_template) {
            case('webwork'):
                $keys = $this->webwork_keys;
                break;
            case('advanced'):
            default:
                $keys = $this->advanced_keys;

        }
        if (!$course_id) {
            $keys = $this->_removeCourseInfo($keys);
        }
        $uploaded_keys = array_keys($bulk_import_questions[0]);
        foreach ($keys as $key => $correct_key) {
            if ($correct_key !== $uploaded_keys[$key]) {
                $response['message'] = ["The CSV should have a first row with the following headings: " . implode(', ', $keys) . "."];
                return $response;
            }
        }
        //structure looks good
        $messages = [];
        $assign_tos = $course_id ?
            Helper::getDefaultAssignTos($course_id)
            : null;

        if ($course_id) {
            $course = Course::find($course_id);
            $message = $course->bulkUploadAllowed();
            if ($message) {
                $response['message'][] = $message;
                return $response;
            }
        }


        $assignment_templates = [];
        DB::beginTransaction();
        foreach ($bulk_import_questions as $key => $question) {
            $bulk_import_questions[$key]['Course'] = $request->course_id;
            $bulk_import_questions[$key]['row'] = $key + 2;
            $bulk_import_questions[$key]['import_status'] = 'Pending';
            if ($question['Tags']) {
                $tags = explode(',', $question['Tags']);
                $bulk_import_questions[$key]['Tags'] = [];
                foreach ($tags as $tag) {
                    $bulk_import_questions[$key]['Tags'][] = trim($tag);
                }
            }
            $row_num = $key + 2;
            if ($import_template === 'advanced' && !in_array($question['Question Type*'], ['assessment', 'exposition'])) {
                $messages[] = "Row $row_num has a Question Type of {$question['Question Type*']} but the valid question types are assessment and exposition.";
            }

            if (!$question['Folder*']) {
                $messages[] = "Row $row_num is missing a Folder.";
            } else {
                $folder = DB::table('saved_questions_folders')
                    ->where('type', 'my_questions')
                    ->where('name', trim($question['Folder*']))
                    ->where('user_id', $request->user()->id)
                    ->select('id')
                    ->first();
                if (!$folder) {
                    $savedQuestionsFolder = new SavedQuestionsFolder();
                    $savedQuestionsFolder->name = trim($question['Folder*']);
                    $savedQuestionsFolder->user_id = $request->user()->id;
                    $savedQuestionsFolder->type = 'my_questions';
                    $savedQuestionsFolder->save();
                    $bulk_import_questions[$key]['folder_id'] = $savedQuestionsFolder->id;
                } else {
                    $bulk_import_questions[$key]['folder_id'] = $folder->id;
                }
            }
            $course_assignment_topic_error = false;
            if ($course_id) {
                $question['Assignment'] = trim($question['Assignment']);
                if ($question['Assignment'] && $question['Template']) {
                    if (!isset($assignment_templates[$question['Assignment']])) {
                        $assignment_templates[$question['Assignment']] = $question['Template'];
                    } else {
                        if ($assignment_templates[$question['Assignment']] !== $question['Template']) {
                            $messages[] = "Row $row_num has an Assignment {$question['Assignment']} and a Template {$question['Template']} but a previous row has the same Assignment with a different Template.";
                        }
                    }
                }
                if (!$question['Assignment']) {
                    $course_assignment_topic_error = true;
                    $messages[] = "Row $row_num is missing an Assignment.";

                }
                if (!$course_assignment_topic_error && $question['Topic'] && !$question['Assignment']) {
                    $messages[] = "Row $row_num has a Topic but not an Assignment.";
                }
                $assignment_error = false;
                $assignment_template_error = false;
                if (!$course_assignment_topic_error && $question['Assignment']) {
                    if (!($assignment = DB::table('assignments')
                        ->join('courses', 'assignments.course_id', '=', 'courses.id')
                        ->where('courses.id', $course_id)
                        ->where('assignments.name', trim($question['Assignment']))
                        ->select("assignments.id AS assignment_id")
                        ->first())) {
                        $course = Course::find($request->course_id);
                        if (!$question['Template']) {
                            $assignment_error = true;
                            $messages[] = "Row $row_num has an assignment which is not in $course->name. In addition, there is no Template that can be used to create an assignment.";
                        } else {
                            $assignment_template = AssignmentTemplate::where('template_name', trim($question['Template']))
                                ->where('user_id', $request->user()->id)
                                ->first();
                            if ($assignment_template) {
                                $assignment_template_error = true;
                                $assignment_info = $assignment_template->toArray();
                                $assignment_info['name'] = $question['Assignment'];
                                $assignment_info['course_id'] = $request->course_id;
                                $assignment_info['order'] = $course->assignments->count() + 1;
                                foreach (['id', 'template_name', 'template_description', 'user_id', 'created_at', 'updated_at', 'assign_to_everyone'] as $value) {
                                    unset($assignment_info[$value]);
                                }
                                $assignment = Assignment::create($assignment_info);
                                $this->addAssignTos($assignment, $assign_tos, $section, $request->user());
                            } else {
                                $assignment_template_error = true;
                                $messages[] = "Row $row_num has the template \"{$question['Template']}\", which is not one of your templates.";
                            }
                        }
                    }
                    if (!$assignment_error && !$assignment_template_error && trim($question['Topic'])) {
                        if (!DB::table('assignment_topics')
                            ->join('assignments', 'assignment_topics.assignment_id', '=', 'assignments.id')
                            ->where('assignments.id', $assignment->assignment_id)
                            ->where('assignment_topics.name', $question['Topic'])
                            ->first()) {
                            $assignmentTopic = new AssignmentTopic();
                            $assignmentTopic->assignment_id = $assignment->assignment_id;
                            $assignmentTopic->name = trim($question['Topic']);
                            $assignmentTopic->save();
                        }
                    }
                }
            }

            if (!is_numeric($question['Public*']) || ((int)$question['Public*'] !== 0 && (int)$question['Public*'] !== 1)) {
                $messages[] = "Row $row_num is missing a valid entry for Public (0 for no and 1 for yes).";
            }
            if (!$question['Title*']) {
                $messages[] = "Row $row_num is missing a Title.";
            }

            if ($import_template === 'advanced' && $question['Question Type*'] === 'exposition' && !$question['Open-Ended Content']) {
                $messages[] = "Row $row_num is an exposition type question and is missing the source.";
            }

            if ($import_template === 'advanced' && $question['Question Type*'] === 'exposition' && ($question['Auto-Graded Technology'] || $question['Technology ID/File Path'])) {
                $messages[] = "Row $row_num is an exposition type question but has an auto-graded technology.";
            }

            if ($import_template === 'advanced'
                && $question['Question Type*'] === 'exposition'
                && ($question['Text Question'] || $question['Answer'] || $question['Solution'] || $question['Hint'])) {
                $messages[] = "Row $row_num is an exposition type question and should not have Text Question, Answer, Solution, or Hint.";
            }

            if ($import_template === 'advanced' && $question['Question Type*'] === 'assessment' && !$question['Open-Ended Content'] && !$question['Auto-Graded Technology']) {
                $messages[] = "Row $row_num is an assessment and needs either an auto-graded technology or source.";
            }

            if ($import_template === 'webwork' && !$question['File Path*']) {
                $messages[] = "Row $row_num does not have a File Path";
            }

            $technology_id = $import_template === 'webwork' ? $question['File Path*'] : $question['Technology ID/File Path'];
            if ($import_template === 'advanced') {
                switch ($question['Auto-Graded Technology']) {
                    case('webwork'):
                        if (!$technology_id) {
                            $messages[] = "Row $row_num uses webwork and is missing the File Path.";
                        }
                        break;
                    case('h5p'):
                    case('imathas'):
                        if (!filter_var($technology_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                            $messages[] = "Row $row_num uses {$question['Auto-Graded Technology']} and requires a positive integer as the Technology ID.";
                        }
                        break;
                    case(''):
                        $bulk_import_questions[$key]['Auto-Graded Technology'] = 'text';
                        break;
                    default:
                        $messages[] = "Row $row_num is using an invalid technology: {$question['Auto-Graded Technology']}.";
                }
            }
            if (!$question['License*']) {
                $messages[] = "Row $row_num is missing a license.";
            } else {
                if (!in_array($question['License*'], $this->getValidLicenses()['licenses'])) {
                    $messages[] = "Row $row_num is using an invalid license: {$question['License*']}.";
                }
            }
            if (!$question['Author*']) {
                $messages[] = "Row $row_num is missing an author.";
            }
        }
        $message = $messages;
        $questions_to_import = $bulk_import_questions;
        return compact('message', 'questions_to_import');
    }

    /**
     * @param Request $request
     * @param string $import_template
     * @param Course|null $course
     * @return array|string|StreamedResponse
     * @throws Exception
     */
    public
    function getBulkUploadTemplate(Request $request, string $import_template, Course $course = null)
    {
        try {
            switch ($import_template) {
                case('webwork-def-file'):
                case('webwork'):
                    $list = $this->webwork_keys;
                    break;
                case('advanced'):
                default:
                    $list = $this->advanced_keys;
            }
            if (!$course) {
                $list = $this->_removeCourseInfo($list);
            }
            $efs_dir = '/mnt/local/';
            $is_efs = is_dir($efs_dir);
            $storage_path = $is_efs
                ? $efs_dir
                : Storage::disk('local')->getAdapter()->getPathPrefix();
            $file = "$storage_path$import_template-bulk-question-import-template.csv";
            $fp = fopen($file, 'w');
            if ($import_template === 'webwork-def-file') {
                $contents = file($request->file);
                $new_list[0] = $list;
                $index = 1;
                foreach ($contents as $line) {
                    if (str_starts_with($line, 'source_file')) {
                        $pg_file = trim(str_replace('source_file = ', '', $line));
                        $row = [];
                        foreach ($list as $key => $item) {
                            $row[$key] = $key === 3 ? "huang_course/$pg_file" : '';
                        }
                        $new_list[$index] = $row;
                        $index++;
                    }
                }
                $list = $new_list;
                foreach ($list as $fields) {
                    fputcsv($fp, $fields);
                }
            } else {
                fputcsv($fp, $list);
            }

            fclose($fp);

            Storage::disk('s3')->put("$import_template-bulk-question-import-template.csv", file_get_contents($file));
            return $import_template === 'webwork-def-file'
                ? Storage::disk('s3')->get("$import_template-bulk-question-import-template.csv")
                : Storage::disk('s3')->download("$import_template-bulk-question-import-template.csv");

        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to download the file.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    /**
     * @param Question $question
     * @return array
     * @throws Exception
     */
    public
    function destroy(Question $question): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('destroy', $question);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $exists_in_assignment = DB::table('assignment_question')
                ->where('question_id', $question->id)
                ->exists();
            if ($exists_in_assignment) {
                $response['message'] = "This question already exists in an assignment and cannot be deleted.";
                return $response;
            }
            if ($question->existsInLearningTree()) {
                $response['message'] = "This question already exists in a Learning Tree and cannot be deleted.";
                return $response;
            }


            DB::beginTransaction();
            $question->cleanUpTags();
            foreach (['adapt_mass_migrations',
                         'adapt_migrations',
                         'qti_imports',
                         'h5p_max_scores',
                         'h5p_video_interactions',
                         'question_learning_outcome',
                         'seeds',
                         'assignment_question_time_on_tasks'
                     ]
                     as $table) {
                $column = in_array($table, ['adapt_migrations', 'adapt_mass_migrations']) ? 'new_page_id' : 'question_id';
                DB::table($table)->where($column, $question->id)->delete();
            }
            $question->delete();
            DB::commit();
            $response['message'] = "The question has been deleted.";
            $response['type'] = 'info';
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to delete this question.  Please try again or contact us for assistance.";
        }

        return $response;

    }


    /**
     * @param Request $request
     * @param Question $question
     * @return array
     * @throws Exception
     */
    public
    function index(Request $request, Question $question): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('index', $question);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $questions = $question->where('question_editor_user_id', $request->user()->id)
                ->orderBy('updated_at', 'desc')
                ->get();

            $tags = DB::table('question_tag')
                ->join('tags', 'question_tag.tag_id', '=', 'tags.id')
                ->whereIn('question_id', $questions->pluck('id'))
                ->select('question_id', 'tag')
                ->get();
            $tags_by_question_id = [];
            foreach ($tags as $tag) {
                $tags_by_question_id[$tag->question_id][] = $tag->tag;
            }
            $extra_htmls = ['text_question',
                'a11y_question',
                'answer_html',
                'solution_html',
                'hint',
                'notes'];
            $dom = new \DomDocument();
            foreach ($questions as $key => $question) {
                foreach ($extra_htmls as $extra_html) {
                    if ($question[$extra_html]) {
                        $html = $question->cleanUpExtraHtml($dom, $question[$extra_html]);
                        $questions[$key][$extra_html] = $html;
                    }
                }
                $questions[$key]['tags'] = $tags_by_question_id[$question->id] ?? ['none'];
                $questions[$key]['updated_at'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($question->updated_at, $request->user()->time_zone);
            }
            $response['my_questions'] = $questions;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve your questions.  Please try again or contact us for assistance.";
        }

        return $response;

    }


    /**
     * @throws Exception
     */
    public
    function update(StoreQuestionRequest   $request,
                    Question               $question,
                    Libretext              $libretext,
                    AssignmentSyncQuestion $assignmentSyncQuestion): array
    {
        return $this->store($request, $question, $libretext, $assignmentSyncQuestion);

    }

    /**
     * @param StoreQuestionRequest $request
     * @param Question $question
     * @param Libretext $libretext
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */
    public
    function store(StoreQuestionRequest   $request,
                   Question               $question,
                   Libretext              $libretext,
                   AssignmentSyncQuestion $assignmentSyncQuestion): array
    {
        $response['type'] = 'error';

        $is_update = isset($request->id);

        $authorized = $is_update ? Gate::inspect('update', [$question, $request->folder_id])
            : Gate::inspect('store', [$question, $request->folder_id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $data = $request->validated();
            if ($data['technology'] === 'qti') {
                foreach ($data as $key => $value) {
                    if (strpos($key, 'qti_') !== false) {
                        unset($data[$key]);
                    }
                }
                $question_type = json_decode($request->qti_json)->questionType;
                switch ($question_type) {
                    case ('multiple_response_select_all_that_apply'):
                    case ('multiple_response_select_n'):
                        $unsets = ['colHeaders', 'rows', 'responses'];
                        break;
                    case ('drop_down_table'):
                    case('highlight_table'):
                        $unsets = ['colHeaders', 'rows'];
                        break;
                    case('highlight_text'):
                        $unsets = ['responses'];
                        break;
                    case('drag_and_drop_cloze'):
                        $unsets = ['correct_responses', 'distractors'];
                        break;
                    case('bow_tie'):
                        $unsets = ['actions_to_take', 'parameters_to_monitor', 'potential_conditions'];
                        break;
                    case('numerical'):
                        $unsets = ['correct_response', 'margin_of_error'];
                        break;
                    case('multiple_response_grouping'):
                    case('matrix_multiple_choice'):
                        $unsets = ['headers', 'rows'];
                        break;
                    default:
                        $unsets = [];
                }
                if ($unsets) {
                    foreach ($unsets as $unset) {
                        unset($data[$unset]);
                    }
                }
            }
            $data['qti_json'] = $data['technology'] === 'qti' ? $request->qti_json : null;
            if ($is_update && $data['qti_json'] && $question->qti_json !== $request->qti_json) {
                DB::table('seeds')->where('question_id', $question->id)
                    ->delete();
            }
            if ($request->question_type === 'exposition') {
                $technology_id = null;
                foreach (['technology_id', 'a11y_technology', 'a11y_technology_id', 'webwork_code', 'text_question', 'answer_html', 'hint'] as $value) {
                    $data[$value] = null;
                }
            } else {
                $technology_id = $data['technology_id'] ?? null;
                $data['a11y_technology'] = $data['a11y_technology'] ?? null;
                $data['a11y_technology_id'] = $data['a11y_technology'] ? $data['a11y_technology_id'] : null;
                $data['webwork_code'] = $request->technology === 'webwork' && $request->new_auto_graded_code === 'webwork'
                    ? $request->webwork_code
                    : null;
                $extra_htmls = ['text_question' => 'Text Question',
                    'answer_html' => 'Answer',
                    'solution_html' => 'Solution',
                    'hint' => 'Hint',
                    'notes' => 'Notes'];
                foreach ($extra_htmls as $extra_html => $extra_html_title) {
                    if (isset($data[$extra_html]) && $data[$extra_html]) {
                        $data[$extra_html] = '<div class="mt-section"><h2 class="editable">' . $extra_html_title . '</h2>' . $data[$extra_html] . "</div>";
                    }
                }
            }

            $data['library'] = 'adapt';
            $tags = [];
            if ($data['tags']) {
                foreach ($data['tags'] as $tag) {
                    $tags[] = trim($tag);
                }
            }
            unset($data['tags']);
            $learning_outcomes = [];
            if (isset($data['learning_outcomes'])) {
                $learning_outcomes = $data['learning_outcomes'];
                unset($data['learning_outcomes']);
            }


            $data['page_id'] = $is_update
                ? Question::find($request->id)->page_id
                : 1 + $question->where('library', 'adapt')->orderBy('id', 'desc')->value('page_id');

            $data['url'] = null;

            $data['technology_iframe'] = $technology_id
                ? $question->getTechnologyIframeFromTechnology($data['technology'], $data['technology_id'])
                : '';

            $non_technology_text = isset($data['non_technology_text']) ? trim($data['non_technology_text']) : '';

            $data['non_technology'] = $non_technology_text !== '';
            $data['non_technology_html'] = $non_technology_text ?: null;
            if ($is_update) {
                if ($data['technology'] !== 'h5p') {
                    $data['h5p_type'] = null;
                }
                if ($question->folderIdRequired($request->user(), Question::find($request->id)->question_editor_user_id)) {
                    $data['question_editor_user_id'] = $request->user()->id;
                }
            } else {
                $data['question_editor_user_id'] = $request->user()->id;
            }

            $data['cached'] = false;
            unset($data['non_technology_text']);
            DB::beginTransaction();
            if ($is_update) {
                $question = Question::find($request->id);
                $question->update($data);
            } else {
                if ($request->bulk_upload_into_assignment && $data['technology'] !== 'text') {
                    $question = Question::where('technology', $data['technology'])
                        ->where('technology_id', $data['technology_id'])
                        ->where('version', 1)
                        ->first();
                    if (!$question) {
                        $question = Question::create($data);
                        $question->page_id = $question->id;
                        $question->save();
                    }
                } else {
                    $question = Question::create($data);
                    $question->page_id = $question->id;
                    $question->save();
                }
            }
            if (!$is_update && $data['technology'] === 'h5p') {
                try {
                    $h5p_info = $question->getH5pInfo($data['technology_id']);
                    if (!$h5p_info['success']) {
                        throw new Exception("Could not get h5p info for question $question->id.");
                    }
                    $question->h5p_type = $h5p_info['h5p_type'];
                    $question->source_url = $h5p_info['source_url'];
                    $question->save();
                } catch (Exception $e) {
                    $h = new Handler(app());
                    $h->report($e);

                }
            }
            $question->addTags($tags);
            $question->addLearningOutcomes($learning_outcomes);
            $question->non_technology_html = $non_technology_text;
            $assignment_name = '';
            if ($request->course_id) { //for bulk uploads
                $assignment = DB::table('assignments')
                    ->join('courses', 'assignments.course_id', '=', 'courses.id')
                    ->where('course_id', $request->course_id)
                    ->where('courses.user_id', $request->user()->id)
                    ->where('assignments.name', $request->assignment)
                    ->select('assignments.id')
                    ->first();
                if (!$assignment) {
                    $response['message'] = "That is not one of your courses.";
                    return $response;
                }
                $assignment = Assignment::find($assignment->id);
                $assignment_id = $assignment->id;
                if (!in_array($question->id, $assignment->questions->pluck('id')->toArray())) {
                    $assignment_question_id = $assignmentSyncQuestion
                        ->store($assignment, $question, new BetaCourseApproval());
                    if ($request->topic) {
                        $topic_id = DB::table('assignment_topics')
                            ->where('assignment_id', $assignment_id)
                            ->where('name', $request->topic)
                            ->first()
                            ->id;
                        DB::table('assignment_question')
                            ->where('id', $assignment_question_id)
                            ->update(['assignment_topic_id' => $topic_id]);
                    }
                }
            }
            if ($request->assignment_id) { //for creating a new question within an assignment
                if (!DB::table('assignments')
                    ->join('courses', 'assignments.course_id', '=', 'courses.id')
                    ->where('assignments.id', $request->assignment_id)
                    ->where('courses.user_id', $request->user()->id)
                    ->select('assignments.id')
                    ->first()) {
                    DB::rollBack();
                    $response['message'] = "That is not one of your assignments.";
                    return $response;
                }
                $assignment = Assignment::find($request->assignment_id);
                $assignment_name = $assignment->name;
                if ($assignment->cannotAddOrRemoveQuestionsForQuestionWeightAssignment()) {
                    DB::rollBack();
                    $response['message'] = "You cannot add a question since there are already submissions and this assignment computes points using question weights.";
                    return $response;
                }
                if (!in_array($question->id, $assignment->questions->pluck('id')->toArray())) {
                    $assignmentSyncQuestion->store($assignment, $question, new BetaCourseApproval());
                }
            }

            if ($request->technology === 'webwork' && $request->new_auto_graded_code === 'webwork') {
                Storage::disk('s3')->put("webwork/$question->id.html", $data['webwork_code']);
            }
            DB::table('empty_learning_tree_nodes')->where('question_id', $question->id)->delete();
            DB::commit();
            $action = $is_update ? 'updated' : 'created';
            $response['message'] = "The question has been $action.";
            if ($request->assignment_id) {
                $response['message'] .= "  In addition, it has been added to $assignment_name.";
            }
            $response['url'] = $technology_id ? $question->getTechnologyURLFromTechnology($data['technology'], $data['technology_id']) : null;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save this question.  Please try again or contact us for assistance.";
        }

        return $response;
    }

    /**
     * @param StoreQuestionRequest $request
     * @param string $h5p_id
     * @param Question $question
     * @return array
     * @throws Exception
     */
    public
    function storeH5P(Request  $request,
                      string   $h5p_id,
                      Question $question): array

    {

        $response['type'] = 'error';
        $assignment_id = $request->assignment_id ?: 0;
        $authorized = Gate::inspect('storeH5P', [$question, $assignment_id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response = $question->storeImportedH5P($request->user()->id, $h5p_id, $request->folder_id, $assignment_id);
        } catch (Exception $e) {
            if (DB::transactionLevel()) {
                DB::rollback();
            }
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save this question.  Please try again or contact us for assistance.";
        }

        return $response;
    }


    /**
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public
    function setQuestionUpdatedAtSession(Request $request)
    {
        $cookie = cookie()->forever('loaded_question_updated_at', $request->loaded_question_updated_at);
        $response['loaded_question_updated_at'] = $request->loaded_question_updated_at;
        return response($response)->withCookie($cookie);
    }

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */
    public
    function initRefreshQuestion(Assignment             $assignment,
                                 Question               $question,
                                 AssignmentSyncQuestion $assignmentSyncQuestion): array
    {
        $response['type'] = 'error';

        try {
            $response['type'] = 'error';
            if (!Helper::isAdmin() && $assignment->isBetaAssignment()) {
                $response['message'] = "You cannot refresh this question since this is a Beta assignment. Please contact the Alpha instructor to request the refresh.";
                return $response;
            }

            $response['question_has_auto_graded_or_file_submissions_in_other_assignments'] = $assignmentSyncQuestion->questionHasAutoGradedOrFileSubmissionsInOtherAssignments($assignment, $question);
            $response['question_has_auto_graded_or_file_submissions_in_this_assignment'] = $assignmentSyncQuestion->questionHasAutoGradedOrFileSubmissionsInThisAssignment($assignment, $question);
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to determine the submission and assignment status for this question.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param Question $question
     * @param Assignment $assignment
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param RefreshQuestionRequest $refreshQuestionRequest
     * @return array
     * @throws Exception
     */
    public
    function refresh(Request                $request,
                     Question               $question,
                     Assignment             $assignment,
                     AssignmentSyncQuestion $assignmentSyncQuestion,
                     RefreshQuestionRequest $refreshQuestionRequest): array
    {

        try {

            $response['type'] = 'error';
            $authorized = Gate::inspect('refreshQuestion', [$question, $assignmentSyncQuestion, $assignment]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }


            DB::beginTransaction();

            if ($request->update_scores
                && !$assignmentSyncQuestion->questionHasAutoGradedOrFileSubmissionsInOtherAssignments($assignment, $question)) {
                $assignmentSyncQuestion->updateAssignmentScoreBasedOnRemovedQuestion($assignment, $question);

                DB::table('submissions')->where('assignment_id', $assignment->id)
                    ->where('question_id', $question->id)
                    ->delete();
                DB::table('submission_files')->where('assignment_id', $assignment->id)
                    ->where('question_id', $question->id)
                    ->delete();
            }

            if ($question->library !== 'adapt') {
                $question->getQuestionIdsByPageId($question->page_id, $question->library, 1);
            }

            $refreshed_question = $refreshQuestionRequest->where('question_id', $question->id)->first();
            if ($refreshed_question) {
                //it may not be there if the Admin does it right from a page
                $refreshed_question->status = 'approved';
                $refreshed_question->save();
            }

            DB::commit();
            $updated_scores_message = $request->update_scores
                ? "All submissions have been removed and your students will need to re-submit."
                : '';
            $response['message'] = "The question has been refreshed.  $updated_scores_message ";

            $response['type'] = 'success';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to refresh the question.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Question $question
     * @return array
     * @throws Exception
     */
    public
    function refreshProperties(Question $question): array
    {

        try {

            $response['type'] = 'error';
            $authorized = Gate::inspect('refreshProperties', [$question]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }

            $response['type'] = 'error';
            if ($question->library !== 'adapt') {
                $question->refreshProperties();
            }
            $response['solution_html'] = $question->solution_html ?: null;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to update the question's properties.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Request $request
     * @return array|string|null
     */
    public
    function getDefaultImportLibrary(Request $request)
    {
        $response['default_import_library'] = $request->cookie('default_import_library') ?? null;
        return $response;
    }

    /**
     * @param Request $request
     * @param Question $question
     * @return array
     * @throws Exception
     */
    public
    function updateProperties(Request $request, Question $question): array
    {

        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('updateProperties', $question);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $question->auto_attribution = $request->auto_attribution;
            $question->attribution = !$request->auto_attribution ? $request->attribution : null;
            $question->private_description = $request->private_description;
            $question->save();
            $response['type'] = 'success';
            $response['message'] = "The question's properties have been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to update the question's properties.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public
    function storeDefaultImportLibrary(Request $request, Libretext $libretext)
    {
        $response['type'] = 'error';
        $libraries = $libretext->libraries();
        $library = $request->default_import_library;
        $cookie = cookie()->forever('default_import_library', null);
        try {
            if ($library === null || in_array($library, $libraries)) {
                $cookie = cookie()->forever('default_import_library', $request->default_import_library);
                $response['type'] = 'success';
                $response['message'] = 'Your default import library has been updated.';
            } else {
                $response['message'] = 'That is not a valid library';
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your default import setting.  Please try again or contact us for assistance.";

        }
        return response($response)->withCookie($cookie);

    }

    /**
     * @param Request $request
     * @param Question $Question
     * @param Assignment $assignment
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param Libretext $libretext
     * @param BetaCourseApproval $betaCourseApproval
     * @return array
     * @throws Exception
     */
    public
    function directImportQuestion(Request                $request,
                                  Question               $Question,
                                  Assignment             $assignment,
                                  AssignmentSyncQuestion $assignmentSyncQuestion,
                                  Libretext              $libretext,
                                  BetaCourseApproval     $betaCourseApproval): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $assignment);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }

        if ($assignment->cannotAddOrRemoveQuestionsForQuestionWeightAssignment()) {
            $response['message'] = "You cannot import questions to this assignment since there are already submissions and this assignment computes points using question weights.";
            return $response;
        }


        try {
            $direct_import = $request->direct_import;
            $type = $request->type;
            if (!in_array($type, ['libretexts id', 'adapt id'])) {
                $response['message'] = "$type is not a valid direct import type.";
                return $response;
            }
            if (!$direct_import) {
                $response['message'] = "You didn't submit anything for direct import.";
                return $response;
            }
            $question_to_add_info = ($type === 'libretexts id')
                ? $Question->getQuestionToAddByPageId($request, $libretext)
                : $Question->getQuestionToAddByAdaptId($request);
            if ($question_to_add_info['type'] === 'error') {
                $response['message'] = $question_to_add_info['message'];
                return $response;
            }
            $question_id = $question_to_add_info['question_id'];
            $direct_import_id = $question_to_add_info['direct_import_id'];
            DB::beginTransaction();
            $assignment_questions = $assignment->questions->pluck('id')->toArray();
            $open_ended_submission_type = $assignment->default_open_ended_submission_type;
            $open_ended_text_editor = $assignment->default_open_ended_text_editor;
            if ($type === 'adapt id') {
                $assignment_question = $question_to_add_info['assignment_question'];
                if ($assignment_question) {
                    $open_ended_submission_type = $assignment_question->open_ended_submission_type;
                    $open_ended_text_editor = $assignment_question->open_ended_text_editor;
                }
            }
            if (!in_array($question_id, $assignment_questions)) {
                $assignmentSyncQuestion->addQuestiontoAssignmentByQuestionId($assignment,
                    $question_id,
                    $assignmentSyncQuestion,
                    $open_ended_submission_type,
                    $open_ended_text_editor,
                    $betaCourseApproval);
                $response['direct_import_id_added_to_assignment'] = $direct_import_id;
            } else {
                $response['direct_import_id_not_added_to_assignment'] = $direct_import_id;
            }
            DB::commit();
            $response['type'] = 'success';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error importing the question.  Please try again or contact us for assistance.";
        }
        return $response;


    }


    public
    function getQuestionsByTags(Request $request, Question $Question)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('viewAny', $Question);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }


        $question_ids = $this->getQuestionIdsByWordTags($request);

        $questions = Question::select('id', 'page_id', 'technology_iframe', 'non_technology', 'library')
            ->whereIn('id', $question_ids)->get();

        $solutions = Solution::select('question_id', 'original_filename')
            ->whereIn('question_id', $question_ids)
            ->where('user_id', Auth::user()->id)
            ->get();

        if (!$solutions->isEmpty()) {
            foreach ($solutions as $key => $value) {
                $solutions[$value->question_id] = $value->original_filename;

            }
        }

        foreach ($questions as $key => $question) {
            $questions[$key]['inAssignment'] = false;
            $questions[$key]['iframe_id'] = $this->createIframeId();
            $questions[$key]['non_technology'] = $question['non_technology'];
            $questions[$key]['non_technology_iframe_src'] = $this->getHeaderHtmlIframeSrc($question);
            $questions[$key]['technology_iframe'] = $this->formatIframeSrc($question['technology_iframe'], $question['iframe_id']);
            $questions[$key]['solution'] = $solutions[$question->id] ?? false;
        }

        return ['type' => 'success',
            'questions' => $questions];

    }

    /**
     * @param Question $question
     * @param RefreshQuestionRequest $refreshQuestionRequest
     * @return array
     * @throws Exception
     */
    public
    function compareCachedAndNonCachedQuestions(Question               $question,
                                                RefreshQuestionRequest $refreshQuestionRequest): array
    {
        $response['type'] = 'error';
        /* $authorized = Gate::inspect('refreshQuestion', $question);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }*/
        try {
            $question_info = Question::select('*')
                ->where('id', $question->id)->first();
            $response['cached_question'] = $question->formatQuestionFromDatabase($question_info);
            $response['uncached_question_src'] = "https://{$question_info['library']}.libretexts.org/@go/page/{$question_info['page_id']}";
            $response['nature_of_update'] = $refreshQuestionRequest->where('question_id', $question->id)
                ->select('nature_of_update')
                ->pluck('nature_of_update')
                ->first();

            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve the old and new questions.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param Question $question
     * @return array
     * @throws Exception
     */
    public
    function getWebworkCodeFromFilePath(Request  $request,
                                        Question $question): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getWebworkCodeFromFilePath', $question);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            if (is_numeric($request->file_path)) {
                $webwork_question = $question
                    ->where('id', $request->file_path)
                    ->where('technology', 'webwork')
                    ->first();
                if ($webwork_question) {
                    $webwork_code = $webwork_question->webwork_code ?: $question->getWebworkCodeFromFilePath($webwork_question->technology_id);
                } else {
                    $response['message'] = "That question is not a weBWork question.";
                    return $response;
                }
            } else {
                $webwork_code = $question->getWebworkCodeFromFilePath($request->file_path);
            }
            $response['webwork_code'] = $webwork_code;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the code from this filepath.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Question $question
     * @param Tag $Tag
     * @return array|StreamedResponse
     * @throws Exception
     */
    public
    function exportWebworkCode(Question $question,
                               Tag      $Tag)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('exportWebworkCode', $question);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $question = Question::where('technology', 'webwork')
            ->where('id', $question->id)
            ->first();
        if (!$question) {
            $response['error'] = "This is not a weBWork question.";
            return $response;
        }
        try {
            $webwork_code = $question->webwork_code ?: $question->getWebworkCodeFromFilePath($question->technology_id);
            $usable_tags = $Tag->getUsableTags([$question->id]);
            $tags = [];
            foreach ($usable_tags as $tag) {
                $tags[] = $tag->tag;
            }
            $comma_separated_tags = $tags ? implode(', ', $tags) : '';
            $meta_tags = "## Meta-tags\r\n";
            $meta_tags .= "## Title: $question->title\r\n";
            $meta_tags .= "## Author: $question->author\r\n";
            if ($question->license) {
                $meta_tags .= "## License: $question->license\r\n";
            }
            if ($question->license_version) {
                $meta_tags .= "## License version: $question->license_version\r\n";
            }
            if ($comma_separated_tags) {
                $meta_tags .= "## Tags: $comma_separated_tags\r\n";
            }
            $source = $question->webwork_code ? "ADAPT" : $question->technology_id;
            $meta_tags .= "## Source: $source\r\n";

            $meta_tags .= "## End Meta-tags \r\n\r\n";
            $filename = preg_replace('/[^a-z0-9]+/', '-', strtolower($question->title));
            return response()->streamDownload(function () use ($meta_tags, $webwork_code) {
                echo $meta_tags . $webwork_code;
            }, "$filename.txt");
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting exporting this code.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Request $request
     * @param Question $question
     * @param Libretext $libretext
     * @return array
     * @throws Exception
     */
    public
    function preview(Request   $request,
                     Question  $question,
                     Libretext $libretext): array
    {
        $response['type'] = 'error';
        try {
            $question['non_technology_iframe_src'] = null;
            $page_id = $request->user()->id;
            $user_id = request()->user()->id;
            $request->non_technology_text
                ? Storage::disk('s3')->put("preview/$user_id.php", $request->non_technology_text)
                : Storage::disk('s3')->delete("preview/$user_id.php");
            $question['library'] = 'preview';
            $question['page_id'] = $page_id;
            if ($request->non_technology_text) {
                $question['non_technology'] = $request->non_technology_text;
                $question['non_technology_iframe_src'] = "/api/get-header-html/preview";
            }
            $question['technology_iframe_src'] = null;
            if ($request->technology !== 'text') {
                if ($request->technology === 'webwork' && $request->webwork_code) {
                    Storage::disk('s3')->put("preview/$page_id.html", $question->getWebworkHtmlFromCode($request->webwork_code));
                    $question['technology_iframe_src'] = Storage::disk('s3')->temporaryUrl("preview/$page_id.html", now()->addMinutes(360));
                } else {
                    $technology_iframe = $question->getTechnologyIframeFromTechnology($request->technology, $request->technology_id);
                    $iframe_id = substr(sha1(mt_rand()), 17, 12);
                    $question['technology_iframe_src'] = $this->formatIframeSrc($technology_iframe, $iframe_id);
                }
            }
            $question['id'] = 'some-id-that-is-not-really-an-id';//just a placeholder for previews
            $response['type'] = 'success';
            $response['question'] = $question;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the question preview.  Please try again or contact us for assistance.";
        }

        return $response;


    }

    /**
     * @throws Exception
     */
    public
    function getRemediationByLibraryAndPageIdInLearningTreeAssignment(Request      $request,
                                                                      Assignment   $assignment,
                                                                      Question     $question,
                                                                      LearningTree $learning_tree,
                                                                      int          $branch_id,
                                                                      int          $active_id,
                                                                      string       $library,
                                                                      int          $page_id): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getRemediationByLibraryAndPageIdInLearningTreeAssignment',
            [$question,
                $assignment,
                $learning_tree,
                $active_id,
                $library,
                $page_id]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $seed = DB::table('seeds')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->where('user_id', $request->user()->id)
                ->select('seed')
                ->first();
            $seed = $seed ? $seed->seed : 1234;
            $question->cacheQuestionFromLibraryByPageId($library, $page_id);
            $remediation_info = Question::select('*')
                ->where('library', $library)
                ->where('page_id', $page_id)
                ->first();
            $remediation_result = $question->formatQuestionFromDatabase($remediation_info);
            $remediation = $question->fill($remediation_result);

            $domd = new \DOMDocument();
            $JWE = new JWE();
            $extra_custom_claims['is_remediation'] = true;
            $extra_custom_claims['learning_tree_id'] = $learning_tree->id;
            $extra_custom_claims['branch_id'] = $branch_id;

            $technology_src_and_problemJWT = $question->getTechnologySrcAndProblemJWT($request, $assignment, $remediation, $seed, true, $domd, $JWE, $extra_custom_claims);
            $technology_src = $technology_src_and_problemJWT['technology_src'];
            $problemJWT = $technology_src_and_problemJWT['problemJWT'];

            if ($technology_src) {
                $iframe_id = $this->createIframeId();
                //don't return if not available yet!
                $remediation['technology_iframe_src'] = $this->formatIframeSrc($question['technology_iframe'], $iframe_id, $problemJWT);
            }
            $remediation['technology_iframe'] = '';//hide this from students since it has the path
            if ($remediation['non_technology_iframe_src']) {
                session()->put('canViewLocallySavedContents', "$library-$page_id");
            }
            $response['remediation'] = $remediation;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the remediation.  Please try again or contact us for assistance.";

        }

        return $response;
    }

    /**
     * @param string $library
     * @param int $page_id
     * @param Question $question
     * @return array
     */
    public
    function getQuestionByLibraryAndPageId(string $library, int $page_id, Question $question): array
    {
        if ($library !== 'adapt') {
            $question->cacheQuestionFromLibraryByPageId($library, $page_id);
        }
        $question_to_show = $question->where('library', $library)->where('page_id', $page_id)->first();
        if (!$question_to_show) {
            $response['type'] = 'error';
            $response['message'] = "There is no question in the ADAPT library with page id $page_id.";
            return $response;
        }
        return $this->show($question_to_show);
    }

    /**
     * @param Question $Question
     * @return array
     * @throws Exception
     */
    public
    function show(Question $Question): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('viewAny', $Question);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }

        $response['type'] = 'error';
        try {
            $question_info = Question::select('*')
                ->where('id', $Question->id)
                ->first();

            $question = $Question->formatQuestionFromDatabase($question_info);
            $user = request()->user();
            if ($user->isMe()) {
                $can_edit = true;
            } else if ($user->role === 5) {
                $can_edit = true;
                $question_editor = User::find($question_info->question_editor_user_id);
                if ($question_editor->role !== 5) {
                    $can_edit = false;
                }
            } else {
                $can_edit = (int)$user->id == $question_info->question_editor_user_id && ($user->role === 2);
            }
            $response['type'] = 'success';
            $question['can_edit'] = $can_edit;
            $response['question'] = $question;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting that question.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Question $question
     * @return array
     * @throws Exception
     */
    public
    function getQuestionToEdit(Question $question): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getQuestionForEditing', $question);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $response['type'] = 'error';
        try {
            $question_to_edit = Question::select('*')
                ->where('id', $question->id)->first();
            $clone_history = [];
            if ($question_to_edit) {
                $current_question = $question_to_edit;
                while ($current_question) {
                    if ($current_question->clone_source_id) {
                        $clone_history[] = $current_question->clone_source_id;
                    }
                    $current_question = Question::where('id', $current_question->clone_source_id)->first();
                }
                $learning_outcomes = DB::table('question_learning_outcome')
                    ->join('learning_outcomes', 'question_learning_outcome.learning_outcome_id', '=', 'learning_outcomes.id')
                    ->select('subject', 'learning_outcomes.id', 'learning_outcomes.description AS label')
                    ->where('question_id', $question_to_edit->id)
                    ->get();

                $question_to_edit['learning_outcomes'] = $learning_outcomes;
                $formatted_question_info = $question->formatQuestionFromDatabase($question_to_edit);
                foreach ($formatted_question_info as $key => $value) {
                    $question_to_edit[$key] = $value;
                }
                $extra_htmls = ['text_question',
                    'a11y_question',
                    'answer_html',
                    'solution_html',
                    'hint',
                    'notes'];
                $dom = new \DomDocument();
                if ($question_to_edit['non_technology']) {
                    $contents = $question_to_edit['non_technology_html'];
                    // dd($contents);
                    $question_to_edit['non_technology_text'] = trim($question->addTimeToS3Images($contents, $dom, false));
                    $question_to_edit['non_technology_text'] = trim(str_replace(array("\n", "\r"), '', $question_to_edit['non_technology_text']));

                    $in_paragraph = substr($question_to_edit['non_technology_text'], 0, 3) === '<p>' && substr($question_to_edit['non_technology_text'], -4) === '</p>';

                    if ($in_paragraph) {
                        //ckeditor was adding an empty paragraph at the start.
                        $question_to_edit['non_technology_text'] = substr($question_to_edit['non_technology_text'], 3);
                        $length = strlen($question_to_edit['non_technology_text']);
                        $question_to_edit['non_technology_text'] = substr($question_to_edit['non_technology_text'], 0, $length - 4);
                    }


                }
                foreach ($extra_htmls as $extra_html) {
                    if ($question_to_edit[$extra_html]) {
                        $question_to_edit[$extra_html] = trim(str_replace(array("\n", "\r"), '', $question_to_edit[$extra_html]));
                        $html = $question->cleanUpExtraHtml($dom, $question_to_edit[$extra_html]);
                        $question_to_edit[$extra_html] = $html;
                    }
                }
                $tags = DB::table('question_tag')
                    ->join('tags', 'question_tag.tag_id', '=', 'tags.id')
                    ->where('question_id', $question->id)
                    ->select('tag')
                    ->get();
                $tags = !empty($tags) ? $tags->pluck('tag')->toArray() : [];


                $question_to_edit['question_exists_in_own_assignment'] = $question->questionExistsInOneOfTheirAssignments();
                $question_to_edit['tags'] = $tags;
                $question_to_edit['clone_history'] = $clone_history;

                $question_to_edit['question_exists_in_another_instructors_assignment'] = $question->questionExistsInAnotherInstructorsAssignments();
                $response['type'] = 'success';
                $response['question_to_edit'] = $question_to_edit;
                //dd($response);
            } else {
                $response['message'] = 'We were not able to locate that question in our database.';
            }

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting that question.  Please try again or contact us for assistance.";
        }
        return $response;

    }


    public
    function getQuestionIdsByWordTags(Request $request)
    {
        $chosen_tags = DB::table('tags')
            ->whereIn('tag', $request->get('tags'))
            ->get()
            ->pluck('id');

        if ($chosen_tags->isEmpty()) {
            echo json_encode([
                'type' => 'error',
                'message' => 'We could not find the tags in our database.']);
            exit;

        }
        $question_ids_grouped_by_tag = [];
        //get all of the question ids for each of the tags
        foreach ($chosen_tags as $key => $chosen_tag) {
            $question_ids_grouped_by_tag[$key] = DB::table('question_tag')
                ->select('question_id')
                ->where('tag_id', '=', $chosen_tag)
                ->get()
                ->pluck('question_id')->toArray();
            if (!$question_ids_grouped_by_tag[$key]) {
                echo json_encode(['type' => 'error',
                    'message' => 'There are no questions associated with those tags.']);
                exit;
            }
        }
        //now intersect them for each group
        $question_ids = $question_ids_grouped_by_tag[0];
        $intersected_question_ids = [];
        foreach ($question_ids_grouped_by_tag as $question_group) {
            $intersected_question_ids = array_intersect($question_ids, $question_group);
        }
        if (!count($intersected_question_ids)) {
            echo json_encode(['type' => 'error',
                'message' => 'There are no questions associated with those tags.']);
            exit;
        }
        return $intersected_question_ids;
    }

    public
    function validatePageId(Request $request)
    {
        $page_id = false;
        foreach ($request->get('tags') as $tag) {
            if (stripos($tag, 'id=') !== false) {
                $page_id = str_ireplace('id=', '', $tag);
            }
        }

        if ($page_id && (count($request->get('tags')) > 1)) {
            $response['message'] = "If you would like to search by page id, please don't include other tags.";
            echo json_encode($response);
            exit;
        }
        return $page_id;
    }

    /**
     * @param $list
     * @return array
     */
    private
    function _removeCourseInfo($list): array
    {
        return array_values(array_diff($list, ['Assignment', 'Template', 'Topic']));
    }
}
