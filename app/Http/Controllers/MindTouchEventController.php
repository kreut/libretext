<?php

namespace App\Http\Controllers;

use App\Question;
use App\Query;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\LOG;

use App\Exceptions\Handler;
use \Exception;

class MindTouchEventController extends Controller
{
    public function update(Request $request)
    {
        LOG::info($request->all());


        /*  Could be:
         * 1.  A new Question
         * 2.  Updated Tags
         * 3.  A change in the location name
         */
        //new tags
        $tags = $request->tags;
        if ($tags) {
            $tags = json_decode($request->tags, true);
            $lower_case_tags = [];
            foreach ($tags as $key => $tag) {
                $lower_case_tags[$key] = mb_strtolower($tag);
            }
        }


        $Query = new Query();
        try {
            DB::beginTransaction();
            $question = Question::where('page_id', $request->page_id)->first();
            if ($question) {
                LOG::info('question exists');
                LOG::info('Current Location: ' . $question->location);
                //maybe the location was updated....
                if ($question->location !== $request->location) {
                    $question->location = $request->location;
                    $question->save();
                }
            } else {
                LOg::info('new question');
                //new question
                $question = Question::create(['page_id' => $request->page_id,
                    'technology' => 'h5p',
                    'location' => $request->location]);
            }
            //handle the tags
            if ($tags) {
                LOG::info('updating tags');
                $question->tags()->detach();//get rid of the current tags
                $Query->addTagsToQuestion($question, $lower_case_tags);
            } else {
                Log::info('not updating tags');
            }
            DB::commit();

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
        }
    }
}
