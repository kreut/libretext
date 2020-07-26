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
        $chosen_tags = Tag::whereIn('tag',  $request->get('tags'));

        if (!$chosen_tags) return ['type' => 'error'];
        $tag_ids = [];
        foreach ($chosen_tags as $chosen_tag){
            array_push($tag_ids, $chosen_tag->id);

        }
        Start here!!!  Then change below
        SELECT question_id
FROM question_tag
WHERE tag_id IN (438,3066)
GROUP BY question_id
HAVING COUNT(*) = 2



        foreach ($tag->questions as $key => $question){
            $tag->questions[$key]['inAssignment'] = false;
        }

        return ['type' => 'success',
            'questions' => $tag->questions];

    }
}
