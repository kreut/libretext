<?php

namespace App\Http\Controllers;

use App\Tag;
use App\Question;
use Illuminate\Http\Request;
use App\Question_Tag;
use \Exception;
use App\Exceptions\Handler;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    public function getQuestionsByTags(Request $request)
    {
        //get all questions with these tags
        //dd($request->get('tags'));
        $chosen_tags = DB::table('tags')
            ->whereIn('tag', $request->get('tags'))
            ->get()
            ->pluck('id');
        if (!$chosen_tags) return ['type' => 'error'];

        $question_ids = DB::table('question_tag')
            ->select('question_id')
            ->whereIn('tag_id', $chosen_tags)
            ->groupBy(['question_id','tag_id'])
            ->having(DB::raw('count(`tag_id`)'), '=', count($chosen_tags))
            ->get()
            ->pluck('question_id');
        $questions = Question::whereIn('id', $question_ids)->get();
        if (!count($questions)) {
            return ['type' => 'error'];
        }
        foreach ($questions as $key => $question) {
            $questions[$key]['inAssignment'] = false;

        }

        return ['type' => 'success',
            'questions' => $questions];

    }
}
