<?php

namespace App\Http\Controllers;

use App\Tag;
use Illuminate\Http\Request;
use \Exception;
use App\Exceptions\Handler;

class QuestionController extends Controller
{
    public function getQuestionsByTags(Request $request)
    {
        //get all questions with these tags
        $tag = Tag::where(['tag' => $request->get('tags')])->first();
        if (!$tag) return ['type' => 'error'];
        foreach ($tag->questions as $key => $question){
            $tag->questions[$key]['inAssignment'] = false;
        }
        return ['type' => 'success',
            'questions' => $tag->questions];

    }
}
