<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\MindTouchTokens;

class MindTouchEvent extends Model
{
    use MindTouchTokens;
    protected $guarded = [];

    public function question(){
        return $this->hasOne('App\Question', 'page_id');
    }

    public
    function saveMindTouchEvents()
    {
        https://api.libretexts.org/endpoint/queryEvents?limit=1000
        $tokens = $this->tokens;
        $token = $tokens->query;
        $command = 'curl -i -H "Accept: application/json" -H "origin: https://dev.adapt.libretexts.org" -H "x-deki-token: ' . $token . '" https://api.libretexts.org/endpoint/queryEvents?limit=1000';

        exec($command, $output, $return_var);
        if ($return_var > 0) {
            Log::error("saveEvents failed with return_var: $return_var");
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
            Log::error("saveMindTouchEvents failed because none of the output started with <summaries");
            exit;
        }

        /**
         * echo $value->event['mt-epoch'] . "\r\n";
         * echo $value->event->page->path . "\r\n";
         **/
        foreach ($xml->children() as $key => $value) {

            $page_id = $value->event->page['id'];
            $event_id = $value->event['id'];
            //if the question exists, add it to the database
            if (DB::table('questions')->where('page_id', $page_id)->first()) {
                MindTouchEvent::firstOrCreate(['event_id' => $event_id,
                    'page_id' => $page_id,
                    'event_time' => date("Y-m-d H:i:s", strtotime($value->event['datetime'])),
                    'event' => $value->event['type'],]);
            }
        }
    }
}
