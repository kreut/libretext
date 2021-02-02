<?php

namespace App\Http\Controllers;


use App\Assignment;
use App\AssignmentSyncQuestion;
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


    public function massImportQuestions(Request $request, Question $Question, Assignment $assignment, AssignmentSyncQuestion $assignmentSyncQuestion)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $assignment);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $mass_import = $request->mass_import;
            if (! $mass_import){
                $response['message'] = "You didn't submit any page ids.";
                return $response;
            }
            $page_ids = explode(',', $mass_import);

            $page_ids_added_to_assignment = [];
            $page_ids_not_added_to_assignment = [];
            $questions_to_add = [];
            foreach ($page_ids as $page_id) {
                if (!(is_numeric($page_id) && is_int(0+$page_id) &&  0+$page_id >0)){
                    $response['message'] = "$page_id should be a positive integer.";
                    return $response;
                }
                $question_id = $Question->getQuestionIdsByPageId($page_id, 'query', true)[0];//returned as an array
                $questions_to_add[ $question_id] = $page_id;
            }

            $assignment_questions = $assignment->questions->pluck('id')->toArray();

            DB::beginTransaction();
            foreach ($questions_to_add as $question_id => $page_id) {
                if (!in_array($question_id, $assignment_questions)) {
                    DB::table('assignment_question')
                        ->insert([
                            'assignment_id' => $assignment->id,
                            'question_id' => $question_id,
                            'order' => $assignmentSyncQuestion->getNewQuestionOrder($assignment),
                            'points' => $assignment->default_points_per_question, //don't need to test since tested already when creating an assignment
                            'open_ended_submission_type' => $assignment->default_open_ended_submission_type]);
                    array_push($page_ids_added_to_assignment, $page_id);
                } else {
                    array_push($page_ids_not_added_to_assignment, $page_id);
                }
                array_push($assignment_questions, $question_id);


            }
            DB::commit();
            $response['type'] = 'success';
            $response['page_ids_added_to_assignment'] = implode($page_ids_added_to_assignment, ', ');
            $response['page_ids_not_added_to_assignment'] = implode($page_ids_not_added_to_assignment, ', ');
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error mass importing these questions.  Please try again or contact us for assistance.";
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

        $page_id = $this->validatePageId($request);


        $question_ids = $page_id ? $Question->getQuestionIdsByPageId($page_id, 'query', false)
            : $this->getQuestionIdsByWordTags($request);

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
            $questions[$key]['technology_iframe'] = $this->formatIframe($question['technology_iframe'], $question['iframe_id']);
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
        $question_info = Question::select('id', 'page_id', 'technology_iframe', 'non_technology')
            ->where('id', $Question->id)->first();

        if ($question_info) {
            $question['iframe_id'] = $this->createIframeId();
            $question['non_technology'] = $question_info['non_technology'];
            $question['non_technology_iframe_src'] = $this->getLocallySavedPageIframeSrc($question_info);
            $question['technology_iframe'] = $this->formatIframe($question_info['technology_iframe'], $question_info['iframe_id']);
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
        if (!$chosen_tags) {
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
