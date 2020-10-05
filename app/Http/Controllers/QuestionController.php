<?php

namespace App\Http\Controllers;

use App\Tag;
use App\Question;
use Illuminate\Http\Request;
use App\Question_Tag;
use App\Query;
use App\Traits\IframeFormatter;

use App\Exceptions\Handler;
use \Exception;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class QuestionController extends Controller
{
    use IframeFormatter;

    public function getQuestionsByTags(Request $request, Question $Question)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('viewAny', $Question);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }

        $page_id = $this->validatePageId($request);


        $question_ids = $page_id ? $this->getQuestionIdsByPageId($request, $page_id, $Question)
            : $this->getQuestionIdsByWordTags($request);

        $questions = Question::select('id', 'page_id', 'technology_iframe', 'non_technology')
                                ->whereIn('id', $question_ids)->get();

        foreach ($questions as $key => $question) {
            $questions[$key]['inAssignment'] = false;
            $questions[$key]['iframe_id'] = $this->createIframeId();
            $questions[$key]['non_technology'] = $question['non_technology'];
            $questions[$key]['non_technology_iframe_src'] =  $question['non_technology'] ? $request->root() . "/storage/{$question['page_id']}.html" : '';
            $questions[$key]['technology_iframe'] = $this->formatIframe($question['technology_iframe'],  $question['iframe_id']);

        }

        return ['type' => 'success',
            'questions' => $questions];

    }

    public function getQuestionIdsByPageId(Request $request, int $page_id, Question $Question)
    {
        $question = $Question::where('page_id', $page_id)->first();
        if (!$question) {

            //maybe it was just created and doesnt' exist yet...
            ///get it from query
            ///enter it into the database if I can get it
            ///
            /// getPageInfoByPageId(int $page_id)
            $Query = new Query();
            try {
               // id=102629;  //Frankenstein test
                $page_info = $Query->getPageInfoByPageId($page_id);
                $contents = $Query->getContentsByPageId($page_id);

                $body = $contents['body'][0];
                $technology_and_tags = $Query->getTechnologyAndTags($page_info);
                if ($technology = $Query->getTechnologyFromBody($body)) {
                    $technology_iframe = $Query->getTechnologyIframeFromBody($body, $technology);

                    $non_technology = str_replace($technology_iframe, '', $body);
                    $has_non_technology = trim($non_technology) !== '';

                    if ($has_non_technology){
                        //Frankenstein type problem
                        $non_technology = $Query->addExtras($request, $non_technology,
                            ['glMol' => strpos($body, '/Molecules/GLmol/js/GLWrapper.js') !== false,
                                'MathJax'=>false]);
                        Storage::disk('public')->put("{$page_id}.html", $non_technology);

                    }
                } else {
                    $technology_iframe = '';
                    $has_non_technology = true;
                    $non_technology = $Query->addExtras($request, $body,
                        ['glMol' => false,
                        'MathJax' => true
                        ]);
                    $technology = 'text';
                    Storage::disk('public')->put("{$page_id}.html",  $non_technology );
                }
                $data = ['page_id' => $page_id,
                    'technology' => $technology,
                    'location' => $page_info['uri.ui'],
                    'non_technology' => $has_non_technology,
                    'technology_iframe' => $technology_iframe];

                $question = Question::firstOrCreate($data);
                if ($technology_and_tags['tags']) {
                    $Query->addTagsToQuestion($question, $technology_and_tags['tags']);
                }

            } catch (Exception $e) {
                echo json_encode(['type' => 'error',
                    'message' => 'We tried getting that page but got the error: <br><br>' . $e->getMessage() . '<br><br>Please email support with questions!',
                    'timeout' => 12000]);
                exit;
            }

        }
        return [$question->id];
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
