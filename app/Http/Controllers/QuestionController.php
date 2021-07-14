<?php

namespace App\Http\Controllers;


use App\Assignment;
use App\AssignmentSyncQuestion;
use App\Http\Requests\UpdateQuestionProperties;
use App\Libretext;
use App\Question;
use Illuminate\Http\Request;
use App\Solution;
use App\Traits\IframeFormatter;
use App\Traits\LibretextFiles;

use App\Exceptions\Handler;
use \Exception;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QuestionController extends Controller
{
    use IframeFormatter;
    use LibretextFiles;

    /**
     * @param Request $request
     * @return array|string|null
     */
    public function getDefaultImportLibrary(Request $request)
    {
        $response['default_import_library'] = $request->cookie('default_import_library') ?? null;
        return $response;
    }

    /**
     * @param UpdateQuestionProperties $request
     * @param Question $question
     * @return array
     * @throws Exception
     */
    public function updateProperties(Request $request, Question $question): array
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

    public function storeDefaultImportLibrary(Request $request, Libretext $libretext)
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
     * @return array
     * @throws Exception
     */
    public function directImportQuestions(Request $request, Question $Question, Assignment $assignment, AssignmentSyncQuestion $assignmentSyncQuestion, Libretext $libretext)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $assignment);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $direct_import = $request->direct_import;
            if (!$direct_import) {
                $response['message'] = "You didn't submit any library-page id's for direct import.";
                return $response;
            }
            $library_text_page_ids = explode(',', $direct_import);

            $library_page_ids_added_to_assignment = [];
            $library_page_ids_not_added_to_assignment = [];
            $questions_to_add = [];
            $libraries = $libretext->libraries();
            $library_texts = [];
            foreach ($libraries as $library_text => $library) {
                $library_texts[] = strtolower($library_text);
            }

            foreach ($library_text_page_ids as $library_text_page_id) {
                $default_import_library = $request->cookie('default_import_library');
                $library_text_exists = strpos($library_text_page_id, '-') !== false;
                if (!$library_text_exists && !$default_import_library) {
                    $response['message'] = "$library_text_page_id should be of the form {library}-{page id}.";
                    return $response;
                }

                if ($default_import_library && !$library_text_exists) {
                    $library = $default_import_library;
                    $page_id = trim($library_text_page_id);
                    $library_text = strtolower($this->getLibraryTextFromLibrary($libraries, $library));
                } else {
                    [$library_text, $page_id] = explode('-', $library_text_page_id);
                    $library_text = strtolower($this->getLibraryTextFromLibrary($libraries, $library_text));
                    if (!in_array($library_text, $library_texts)) {
                        $response['message'] = "$library_text is not a valid library.";
                        return $response;
                    }
                    $library = $this->getLibraryFromLibraryText($libraries, $library_text);
                }
                if (!(is_numeric($page_id) && is_int(0 + $page_id) && 0 + $page_id > 0)) {
                    $response['message'] = "$page_id should be a positive integer.";
                    return $response;
                }

                $question_id = $Question->getQuestionIdsByPageId($page_id, $library, true)[0];//returned as an array
                $questions_to_add[$question_id] = "$library_text-$page_id";
            }
            $assignments = [$assignment];
            $beta_assignments = $assignment->betaAssignments();

            foreach ($beta_assignments as $beta_assignment) {
                $assignments[] = $beta_assignment;
            }
            DB::beginTransaction();
            foreach ($assignments as $key => $assignment) {
                $assignment_questions = $assignment->questions->pluck('id')->toArray();
                foreach ($questions_to_add as $question_id => $library_text_page_id) {
                    if (!in_array($question_id, $assignment_questions)) {
                        DB::table('assignment_question')
                            ->insert([
                                'assignment_id' => $assignment->id,
                                'question_id' => $question_id,
                                'order' => $assignmentSyncQuestion->getNewQuestionOrder($assignment),
                                'points' => $assignment->default_points_per_question, //don't need to test since tested already when creating an assignment
                                'open_ended_submission_type' => $assignment->default_open_ended_submission_type,
                                'open_ended_text_editor' => $assignment->default_open_ended_text_editor]);
                        if ($key === 0) {//just do this for the alpha assignment
                            array_push($library_page_ids_added_to_assignment, $library_text_page_id);
                        }
                    } else {
                        if ($key === 0) { //just do this for the alpha assignment
                            array_push($library_page_ids_not_added_to_assignment, $library_text_page_id);
                        }
                    }
                    array_push($assignment_questions, $question_id);
                }
            }
            DB::commit();
            $response['page_ids_added_to_assignment'] = implode(', ', $library_page_ids_added_to_assignment);
            $response['page_ids_not_added_to_assignment'] = implode(', ', $library_page_ids_not_added_to_assignment);
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error importing these questions.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param $libraries
     * @param $library_text
     * @return false
     */
    public function getLibraryFromLibraryText($libraries, $library_text)
    {
        $response = false;
        foreach ($libraries as $key => $library) {
            //works for the name or abbreviations
            if (strtolower($key) === $library_text || $library === $library_text) {
                $response = $library;
            }
        }
        return $response;
    }

    /**
     * @param $libraries
     * @param $library
     * @return int|string
     */
    public function getLibraryTextFromLibrary($libraries, $library)
    {
        $response = $library;
        foreach ($libraries as $library_text => $value) {
            if ($value === trim($library)) {
                $response = $library_text;
            }
        }
        return $response;
    }


    public function getQuestionsByTags(Request $request, Question $Question)
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

    public function show(Request $request, Question $Question)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('viewAny', $Question);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        $question = [];
        $response['type'] = 'error';
        $question_info = Question::select('*')
            ->where('id', $Question->id)->first();

        if ($question_info) {

            $question['id'] = $question_info['id'];
            $question['iframe_id'] = $this->createIframeId();
            $question['non_technology'] = $question_info['non_technology'];
            $question['non_technology_iframe_src'] = $this->getLocallySavedPageIframeSrc($question_info);
            $question['technology_iframe'] = $this->formatIframeSrc($question_info['technology_iframe'], $question['iframe_id']);
            if ($question_info['technology'] === 'webwork') {
                //since it's the instructor, show the answer stuff
                $question['technology_iframe'] = str_replace('&amp;showScoreSummary=0&amp;showAnswerTable=0',
                    '',
                    $question['technology_iframe']);
            }
            $response['type'] = 'success';
            $response['question'] = $question;
        } else {
            $response['message'] = 'We were not able to locate that question in our database.';
        }

        return $response;

    }


    public function getQuestionIdsByWordTags(Request $request)
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

    public function validatePageId(Request $request)
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
}
