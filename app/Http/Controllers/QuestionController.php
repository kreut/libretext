<?php

namespace App\Http\Controllers;


use App\Assignment;
use App\AssignmentSyncQuestion;
use App\AssignmentTopic;
use App\BetaCourseApproval;
use App\Course;
use App\Helpers\Helper;
use App\Http\Requests\StoreQuestionRequest;
use App\JWE;
use App\LearningTree;
use App\Libretext;
use App\Question;
use App\SavedQuestionsFolder;
use App\Traits\DateFormatter;
use App\RefreshQuestionRequest;
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
            'Topic',
            "Source",
            "Auto-Graded Technology",
            "Technology ID/File Path",
            "Author",
            "License",
            "License Version",
            "Tags",
            "Text Question",
            "A11Y Question",
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
        $response['licenses'] = ['publicdomain', 'ccby', 'ccbynd', 'ccbync', 'ccbyncnd', 'cbyncsa', 'gnu', 'arr', 'gnufdl'];
        return $response;
    }

    /**
     * @param Request $request
     * @param Question $question
     * @param SavedQuestionsFolder $savedQuestionsFolder
     * @param AssignmentTopic $assignmentTopic
     * @return array
     * @throws Exception
     */
    public
    function validateBulkImportQuestions(Request              $request,
                                         Question             $question,
                                         SavedQuestionsFolder $savedQuestionsFolder,
                                         AssignmentTopic      $assignmentTopic): array
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
            if (!app()->environment('testing')) {
                if (!$request->file('bulk_import_questions_file')) {
                    $response['message'] = ['No file was selected.'];
                    return $response;
                }
                $bulk_import_questions_file = $request->file('bulk_import_questions_file')->store("override-scores/" . Auth()->user()->id, 'local');
                $csv_file = Storage::disk('local')->path($bulk_import_questions_file);

                if (!in_array($request->file('bulk_import_questions_file')->getMimetype(), ['application/csv', 'text/plain'])) {
                    $response['message'] = ["This is not a .csv file."];
                    return $response;
                }
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


            if ($course_id) {
                $beta_courses = DB::table('courses')
                    ->join('beta_courses', 'courses.id', '=', 'beta_courses.alpha_course_id')
                    ->where('courses.id', $course_id)
                    ->select('courses.name AS name')
                    ->get();
                if ($beta_courses->isNotEmpty()) {
                    $response['message'][] = "Bulk upload is not possible for Alpha courses which already have Beta courses.  You can always make a copy of the course and upload these questions to the copied course.";
                    return $response;
                }


                $course_enrollments = DB::table('courses')
                    ->join('enrollments', 'courses.id', '=', 'enrollments.course_id')
                    ->join('users', 'enrollments.user_id', '=', 'users.id')
                    ->where('courses.id', $course_id)
                    ->where('fake_student', 0)
                    ->where('courses.user_id', $request->user()->id)
                    ->select('courses.name AS name')
                    ->get();
                if ($course_enrollments->isNotEmpty()) {
                    $response['message'][] = "Bulk upload is only possible for courses without any enrollments.  Please make a copy of the course and upload these questions to the copied course.";
                    return $response;
                }
            }


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
                    if (!$question['Assignment']) {
                        $course_assignment_topic_error = true;
                        $messages[] = "Row $row_num is missing an Assignment.";

                    }
                    if (!$course_assignment_topic_error && $question['Topic'] && !$question['Assignment']) {
                        $messages[] = "Row $row_num has a Topic but not an Assignment.";
                    }
                    $assignment_error = false;
                    if (!$course_assignment_topic_error && $question['Assignment']) {
                        if (!($assignment = DB::table('assignments')
                            ->join('courses', 'assignments.course_id', '=', 'courses.id')
                            ->where('courses.id', $course_id)
                            ->where('assignments.name', trim($question['Assignment']))
                            ->select("assignments.id AS assignment_id")
                            ->first())) {
                            $assignment_error = true;
                            $course = Course::find($request->course_id);
                            $messages[] = "Row $row_num has an assignment which is not in $course->name.";
                        }
                        if (!$assignment_error && trim($question['Topic'])) {
                            if (!DB::table('assignment_topics')
                                ->join('assignments', 'assignment_topics.assignment_id', '=', 'assignments.id')
                                ->where('assignments.id', $assignment->assignment_id)
                                ->where('assignment_topics.name', $question['Topic'])
                                ->first()) {
                                $assignmentTopic = new AssignmentTopic();
                                $assignmentTopic->assignment_id = $assignment->assignment_id;
                                Log::info($question['Topic']);
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

                if ($import_template === 'advanced' && $question['Question Type*'] === 'exposition' && !$question['Source']) {
                    $messages[] = "Row $row_num is an exposition type question and is missing the source.";
                }

                if ($import_template === 'advanced' && $question['Question Type*'] === 'exposition' && ($question['Auto-Graded Technology'] || $question['Technology ID/File Path'])) {
                    $messages[] = "Row $row_num is an exposition type question but has an auto-graded technology.";
                }

                if ($import_template === 'advanced'
                    && $question['Question Type*'] === 'exposition'
                    && ($question['Text Question'] || $question['A11Y Question'] || $question['Answer'] || $question['Solution'] || $question['Hint'])) {
                    $messages[] = "Row $row_num is an exposition type question and should not have Text Question, A11Y Question, Answer, Solution, or Hint.";
                }

                if ($import_template === 'advanced' && $question['Question Type*'] === 'assessment' && !$question['Source'] && !$question['Auto-Graded Technology']) {
                    $messages[] = "Row $row_num is an assessment and needs either an auto-graded technology or source.";
                }

                if ($import_template === 'webwork' && !$question['File Path*']) {
                    $messages[] = "Row $row_num does not have a File Path";
                }

                $technology_id = $import_template === 'webwork' ? $question['File Path*'] : $question['Technology ID/File Path'];
                if ($import_template === 'advanced' && $question['Auto-Graded Technology']) {
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
                            //no technologu
                            break;
                        default:
                            $messages[] = "Row $row_num is using an invalid technology: {$question['Auto-Graded Technology']}.";
                    }
                }
                if ($question['License'] !== '' && !in_array($question['License'], $this->getValidLicenses()['licenses'])) {
                    $messages[] = "Row $row_num is using an invalid license: {$question['License']}.";
                }
            }

            if ($messages) {
                $response['message'] = $messages;
                return $response;
            }

            $response['questions_to_import'] = $bulk_import_questions;
            $response['type'] = 'success';

        } catch (Exception $e) {

            $h = new Handler(app());
            $h->report($e);
            $response['message'] = ["We were not able to upload the questions file.  Please try again or contact us for assistance."];
        }
        return $response;

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
            $technology_id = $data['technology_id'] ?? null;
            $extra_htmls = ['text_question' => 'Text Question',
                'a11y_question' => 'A11Y Question',
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
                $question = Question::create($data);
                $question->page_id = $question->id;
                $question->save();
            }

            $question->addTags($tags);
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
                $assignment_question_id = $assignmentSyncQuestion
                    ->store(Assignment::find($assignment_id), $question, new BetaCourseApproval());
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
     * @return array
     * @throws Exception
     */
    public
    function storeH5P(Request  $request,
                      string   $h5p_id,
                      Question $question): array

    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('storeH5P', $question);
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
            if (!filter_var($h5p_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                $response['message'] = "$h5p_id should be a positive integer.";
            }

            if (DB::table('questions')
                ->where('technology_iframe', 'like', "%https://studio.libretexts.org/h5p/$h5p_id/embed%")
                ->exists()) {
                $response['message'] = "A question already exists with ID $h5p_id.";
                return $response;
            }
            $h5p = $question->getH5PInfo($h5p_id);
            if (!$h5p['success']) {
                $response['h5p'] = $h5p;
                $response['message'] = "$h5p_id is not a valid id.";
                return $response;
            }
            $tags = $h5p['tags'];
            $data['question_type'] = 'assessment';
            $data['license'] = $h5p['license'];
            $data['author'] = $h5p['author'];
            $data['title'] = $h5p['title'];
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

            DB::beginTransaction();

            $question = Question::create($data);
            $question->page_id = $question->id;
            $question->save();
            $question->addTags($tags);

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
                $technology_iframe = $question->getTechnologyIframeFromTechnology($request->technology, $request->technology_id);
                $iframe_id = substr(sha1(mt_rand()), 17, 12);
                $question['technology_iframe_src'] = $this->formatIframeSrc($technology_iframe, $iframe_id);
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
                                                                      int          $active_id,
                                                                      string       $library,
                                                                      int          $page_id)
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
            $question->cacheQuestionFromLibraryByPageId($library, $page_id);
            $remediation_info = Question::select('*')
                ->where('library', $library)
                ->where('page_id', $page_id)
                ->first();
            $remediation_result = $question->formatQuestionFromDatabase($remediation_info);
            $remediation = $question->fill($remediation_result);

            $seed = 12345;
            $domd = new \DOMDocument();
            $JWE = new JWE();
            $extra_custom_claims['is_remediation'] = true;
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
        $question->cacheQuestionFromLibraryByPageId($library, $page_id);
        return $this->show($question->where('library', $library)->where('page_id', $page_id)->first());
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
    private function _removeCourseInfo($list): array
    {
        return array_values(array_diff($list, ['Assignment', 'Topic']));
    }
}
