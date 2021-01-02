<?php

namespace App;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\MindTouchTokens;

use App\Exceptions\Handler;
use \Exception;

class MindTouchEvent extends Model
{
    use MindTouchTokens;

    protected $guarded = [];
    protected $tokens;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->tokens = $this->getTokens();
    }

    public function question()
    {
        return $this->hasOne('App\Question', 'page_id');
    }

    public
    function updateQuestionsByMindTouchEvents()
    {
        https://api.libretexts.org/endpoint/queryEvents?limit=1000
        $token = $this->tokens->query;
        $command = 'curl -i -H "Accept: application/json" -H "origin: https://dev.adapt.libretexts.org" -H "x-deki-token: ' . $token . '" https://api.libretexts.org/endpoint/queryEvents?limit=1000';

        exec($command, $output, $return_var);
        if ($return_var > 0) {
            Log::error("updateQuestionsByMindTouchEvents failed with return_var: $return_var");
            exit;
        }
        $has_summaries = false;
        foreach ($output as $key => $value) {
            if (strpos($value, '<summaries') === 0) {
                $has_summaries = true;
                $xml = simplexml_load_string($value);
                break;
            }
        }
        if (!$has_summaries) {
            Log::error("updateQuestionsByMindTouchEvents failed because none of the output started with <summaries");
            exit;
        }
Log::info('Finished curl');
        /**
         * echo $value->event['mt-epoch'] . "\r\n";
         * echo $value->event->page->path . "\r\n";
         **/
        foreach ($xml->children() as $key => $value) {

            $page_id = $value->event->page['id'];
            $event_id = $value->event['id'];
            //if the question exists, add it to the database
            if (DB::table('questions')->where('page_id', $page_id)->first()) {
                MindTouchEvent::firstOrCreate(['id' => $event_id,
                    'page_id' => $page_id,
                    'event_time' => date("Y-m-d H:i:s", strtotime($value->event['datetime'])),
                    'event' => $value->event['type'],]);
            }
        }
        Log::info('Saved events to database');
        //now update the events....
        $events_to_update = DB::table('mind_touch_events')->where('status', null)->get();
        $Question = new Question();
        if ($events_to_update->isNotEmpty()) {
            Log::info('Updating questions by mind touch events.');
            foreach ($events_to_update as $key => $event) {
                Log::info('updating ' . $event->page_id);
                try {
                    $updated = $Question->getQuestionIdsByPageId($event->page_id, 'query',true);
                } catch (Exception $e) {
                    Log::info($event->page_id . ' ' . $e->getMessage());
                }
                usleep(50000);
                Log::info('updatePageResult ' . $event->page_id);
                if ($updated) {
                    $page_to_update = MindTouchEvent::where('id', $event->id)->first();
                    $page_to_update->status = 'updated';
                    $page_to_update->save();

                } else {
                    Log::info('Did not update ' . $event->page_id);
                }

            }
        } else {
            Log::info('No questions to update by mind touch events.');
        }
        Log::info('Finished updating events.');
    }
}
