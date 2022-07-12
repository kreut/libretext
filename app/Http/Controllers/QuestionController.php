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
            'Author',
            'License',
            'License Version',
            'Tags'];
        $this->advanced_keys = ["Question Type*",
            "Public*",
            'Folder*',
            "Title*",
            'Assignment',
            "Template",
            'Topic',
            "Header HTML",
            "Auto-Graded Technology",
            "Technology ID/File Path",
            "Author",
            "License",
            "License Version",
            "Tags",
            "Text Question",
            "Answer",
            "Solution",
            "Hint"
        ];

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
        $response['licenses'] = ['publicdomain', 'ccby', 'ccbynd', 'ccbync', 'ccbyncnd', 'ccbyncsa', 'ccbysa', 'gnu', 'arr', 'gnufdl', 'imathascomm'];
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
            if ($course_id) {
                if (count($header) < count($this->advanced_keys)) {
                    $response['message'] = ["It looks like you are trying to import your .csv file into a course but the .csv file is missing some of the course items in the header."];
                    return $response;
                }
            } else {
                if (count($header) > count($this->_removeCourseInfo($this->advanced_keys))) {
                    Log::info(print_r($header, true));
                    Log::info(print_r($this->_removeCourseInfo($this->advanced_keys), true));
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

            if ($import_template === 'advanced' && $question['Question Type*'] === 'exposition' && !$question['Header HTML']) {
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

            if ($import_template === 'advanced' && $question['Question Type*'] === 'assessment' && !$question['Header HTML'] && !$question['Auto-Graded Technology']) {
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
            if ($question['License'] !== '' && !in_array($question['License'], $this->getValidLicenses()['licenses'])) {
                $messages[] = "Row $row_num is using an invalid license: {$question['License']}.";
            }
        }
        $message = $messages;
        $questions_to_import = $bulk_import_questions;
        return compact('message', 'questions_to_import');
    }

    /**
     * @param string $import_template
     * @param Course|null $course
     * @return array|StreamedResponse
     * @throws Exception
     */
    public
    function getBulkUploadTemplate(string $import_template, Course $course = null)
    {
        try {
            switch ($import_template) {
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
            fputcsv($fp, $list);

            fclose($fp);


            Storage::disk('s3')->put("$import_template-bulk-question-import-template.csv", file_get_contents($file));
            return Storage::disk('s3')->download("$import_template-bulk-question-import-template.csv");

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
            DB::table('qti_imports')->where('question_id', $question->id)->delete();
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
                if (json_decode($request->qti_json)->questionType === 'numerical') {
                    unset($data['correct_response']);
                    unset($data['margin_of_error']);
                }

            }
            $data['qti_json'] = $data['technology'] === 'qti' ? $request->qti_json : null;
            $technology_id = $data['technology_id'] ?? null;
            $data['a11y_technology'] = $data['a11y_technology'] ?? null;
            $data['a11y_technology_id'] = $data['a11y_technology_id'] ?? null;

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
            $data['question_editor_user_id'] = $request->user()->id;
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

            $question->addTags($tags);
            $question->addLearningOutcomes($learning_outcomes);
            $question->storeNonTechnologyText($non_technology_text, 'adapt', $question->id, $libretext);
            if ($request->course_id) {
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
                $assignment_id = $assignment->id;
                $assignment = Assignment::find($assignment_id);
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
            if ($request->create_auto_graded_code === 'webwork') {
                Storage::disk('s3')->put("webwork/$question->id.html", $data['webwork_code']);
            }
            DB::table('empty_learning_tree_nodes')->where('question_id', $question->id)->delete();
            DB::commit();
            $action = $is_update ? 'updated' : 'created';
            $response['message'] = "The question has been $action.";
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
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */
    public
    function storeH5P(Request                $request,
                      string                 $h5p_id,
                      Question               $question,
                      AssignmentSyncQuestion $assignmentSyncQuestion): array

    {

        $response['type'] = 'error';
        $assignment_id = $request->assignment_id;
        $authorized = Gate::inspect('storeH5P', [$question, $assignment_id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $data['library'] = 'adapt';
            if (!DB::table('saved_questions_folders')
                ->where('id', $request->folder_id)
                ->where('type', 'my_questions')
                ->where('user_id', $request->user()->id)
                ->first()) {
                $response['message'] = "That is not one of your My Questions folders.";
                return $response;
            }
            $h5p_id = trim($h5p_id);
            if (!filter_var($h5p_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                $response['message'] = "$h5p_id should be a positive integer.";
            }
            $existing_question = Question::where('technology_id', $h5p_id)->first();

            $h5p = $question->getH5PInfo($h5p_id);
            if ($existing_question) {
                if (!$assignment_id) {
                    $response['h5p'] = $h5p;
                    $my_favorites_folder = DB::table('saved_questions_folders')
                        ->where('user_id', $request->user()->id)
                        ->where('type', 'my_favorites')
                        ->orderBy('id')
                        ->first();
                    if (!$my_favorites_folder) {
                        $saved_questions_folder = new SavedQuestionsFolder();
                        $saved_questions_folder->user_id = $request->user()->id;
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
                        ->where('user_id', $request->user()->id)
                        ->where('question_id', $existing_question->id)
                        ->first()) {
                        $message = " (Already exists in ADAPT in your My Favorites folder '$saved_questions_folder->name')";
                    } else {
                        $my_favorites = new MyFavorite();
                        $my_favorites->user_id = $request->user()->id;
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
                $data['h5p_type_id'] = $h5p['h5p_type_id'];
                $data['notes'] = $h5p['body']
                    ? '<div class="mt-section"><span id="Notes"></span><h2 class="editable">Notes</h2>' . $h5p['body'] . '</div>'
                    : '';
                $data['technology'] = 'h5p';
                $data['license_version'] = $h5p['license_version'];
                $data['question_editor_user_id'] = $request->user()->id;
                $data['url'] = null;
                $data['technology_id'] = $h5p_id;
                $data['technology_iframe'] = $question->getTechnologyIframeFromTechnology('h5p', $h5p_id);
                $data['non_technology'] = 0;
                $data['cached'] = true;
                $data['public'] = 0;
                $data['page_id'] = 1 + $question->where('library', 'adapt')->orderBy('page_id', 'desc')->value('page_id');
                $data['folder_id'] = $request->folder_id;
            }
            DB::beginTransaction();
            if (!$existing_question) {
                $question = Question::create($data);
                $question->page_id = $question->id;
                $question->save();
                $question->addTags($tags);
            } else {
                $question = $existing_question;
            }

            if ($assignment_id) {
                $assignment = Assignment::find($assignment_id);
                if (!in_array($question->id, $assignment->questions->pluck('id')->toArray())) {
                    $assignmentSyncQuestion->store($assignment, $question, new BetaCourseApproval());
                }
            }
            DB::commit();

            $response['h5p'] = $h5p;
            $response['type'] = 'success';

        } catch (Exception $e) {
            DB::rollback();
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
            $question->refreshProperties();
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
            if (!in_array($question_id, $assignment_questions)) {
                $open_ended_submission_type = $assignment->default_open_ended_submission_type;
                $open_ended_text_editor = $assignment->default_open_ended_text_editor;
                if ($type === 'adapt id') {
                    $assignment_question = $question_to_add_info['assignment_question'];
                    if ($assignment_question) {
                        $open_ended_submission_type = $assignment_question->open_ended_submission_type;
                        $open_ended_text_editor = $assignment_question->open_ended_text_editor;
                    }
                }
                switch ($assignment->points_per_question) {
                    case('number of points'):
                        $points = $assignment->default_points_per_question;
                        $weight = null;
                        break;
                    case('question weight'):
                        $points = 0;//will be updated below
                        $weight = 1;
                        break;
                    default:
                        throw new exception ("Invalid points_per_question");
                }

                DB::table('assignment_question')
                    ->insert([
                        'assignment_id' => $assignment->id,
                        'question_id' => $question_id,
                        'order' => $assignmentSyncQuestion->getNewQuestionOrder($assignment),
                        'points' => $points,
                        'weight' => $weight,
                        'open_ended_submission_type' => $open_ended_submission_type,
                        'completion_scoring_mode' => $assignment->scoring_type === 'c' ? $assignment->default_completion_scoring_mode : null,
                        'open_ended_text_editor' => $open_ended_text_editor]);
                $assignmentSyncQuestion->updatePointsBasedOnWeights($assignment);
                $betaCourseApproval->updateBetaCourseApprovalsForQuestion($assignment, $question_id, 'add');
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
            $questions[$key]['non_technology_iframe_src'] = $this->getLocallySavedPageIframeSrc($question);
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
    public function getWebworkCodeFromFilePath(Request  $request,
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
    public function exportWebworkCode(Question $question,
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
            if ($request->non_technology_text) {
                $question->storeNonTechnologyText($request->non_technology_text, 'preview', $page_id, $libretext);
                $question['library'] = 'preview';
                $question['page_id'] = $page_id;
                $question['non_technology'] = true;
                $question['non_technology_iframe_src'] = $this->getLocallySavedPageIframeSrc($question);
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

    public
    function show(Question $Question)
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
                ->where('id', $Question->id)->first();

            if ($question_info) {
                $question = $Question->formatQuestionFromDatabase($question_info);
                $response['type'] = 'success';
                $response['question'] = $question;
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
            if ($question_to_edit) {
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
                if (Storage::disk('s3')->has("{$question_to_edit['library']}/{$question_to_edit['page_id']}.php")) {
                    $contents = Storage::disk('s3')->get("{$question_to_edit['library']}/{$question_to_edit['page_id']}.php");
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
