<?php

namespace App\Http\Controllers;

use App\Tag;
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
        $chosen_tags =  DB::table('tags')
                        ->whereIn('tag',  $request->get('tags'))
                        ->get()
                        ->pluck('id');
        if (!$chosen_tags) return ['type' => 'error'];

        $tag = DB::table('question_tag')
                ->where('tag_id', $chosen_tags)
                ->groupBy('question_id')
                ->having('count', '=', count($chosen_tags))
                ->get();
        
        if (!$tag->questions){
            return ['type' => 'error'];
        }
        if ($tag->questions) {
            foreach ($tag->questions as $key => $question) {
                $tag->questions[$key]['inAssignment'] = false;
            }
        }

        return ['type' => 'success',
            'questions' => $tag->questions];

    }
}
