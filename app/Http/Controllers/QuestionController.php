<?php

namespace App\Http\Controllers;

use App\Tag;
use App\Question;
use Illuminate\Http\Request;
use App\Question_Tag;

use App\Exceptions\Handler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class QuestionController extends Controller
{
    public function getQuestionsByTags(Request $request, Question $question)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('viewAny', $question);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message() ;
            return $response;
        }


        $chosen_tags = DB::table('tags')
            ->whereIn('tag', $request->get('tags'))
            ->get()
            ->pluck('id');
        if (!$chosen_tags) return ['type' => 'error'];
        $question_ids_grouped_by_tag = [];
        //get all of the question ids for each of the tags
        foreach ($chosen_tags as $key => $chosen_tag) {
            $question_ids_grouped_by_tag[$key] = DB::table('question_tag')
                                                ->select('question_id')
                                                ->where('tag_id', '=', $chosen_tag)
                                                ->get()
                                                 ->pluck('question_id')->toArray();
            if (!$question_ids_grouped_by_tag[$key] ){
                return ['type' => 'error',
                        'message' => 'There are no questions associated with those tags.'];
            }
        }
        //now intersect them for each group
        $question_ids = $question_ids_grouped_by_tag[0];
        foreach ($question_ids_grouped_by_tag as $question_group) {
            $intersected_question_ids = array_intersect($question_ids, $question_group);
        }
        if (!count($intersected_question_ids)) {
           return ['type' => 'error',
                'message' => 'There are no questions associated with those tags.'];
        }

        $questions = Question::whereIn('id', $intersected_question_ids)->get();

        foreach ($questions as $key => $question) {
            $questions[$key]['inAssignment'] = false;

        }

        return ['type' => 'success',
            'questions' => $questions];

    }
}
