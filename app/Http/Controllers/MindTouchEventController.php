<?php

namespace App\Http\Controllers;

use App\MindTouchEvent;
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
        $staging = (env('APP_ENV') === 'staging');
        $page_id = $request->page_id;
        if (!$page_id) {
            LOG:info('No page id');
            return false;
        }
        if ($staging) {
            $page_id = 1939; //for testing purposes
            //Works if you update a tag or title is updated
        }
        //first save the latest updates
        try {


            //save the latest updates; this one should now be available.
            sleep(2); //not the best!  but allow for race conditions; want MindTouch to do the update first
            $Query = new Query();
            $page_info = $Query->getPageInfoByPageId($page_id);
            LOG::info($page_info);

            $question = Question::where('page_id', $page_id)->first();
            DB::beginTransaction();
            if (!$question) {
                LOG::info('creating');
                //get the info from query then add to the database
                $question = Question::create(['page_id' => $page_id,
                    'technology' => 'h5p',
                    'location' => $page_info['uri.ui']]);
            } else {
                //the path may have changed so I need to update it
                $question->location = $page_info['uri.ui'];
                $question->save();
                LOG::info('updating');

            }

            //now get the tags from Query and update
            $tag_info = $Query->getTagsByPageId($page_id);
            $tags = [];
            LOG::info('getting tags');
            LOG::info($tag_info);
            if ($tag_info['@count'] > 0) {
                foreach ($tag_info['tag'] as $key => $tag) {
                    $tags[] = $tag['@value'];
                }
                $Query->addTagsToQuestion($question, $tags);
            }
            DB::commit();

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
        }
    }
}
