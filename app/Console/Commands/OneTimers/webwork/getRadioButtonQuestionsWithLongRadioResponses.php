<?php

namespace App\Console\Commands\OneTimers\webwork;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class getRadioButtonQuestionsWithLongRadioResponses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:RadioButtonQuestionsWithLongRadioResponses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            DB::beginTransaction();


            $questions = DB::table('questions')
                ->select('id', 'webwork_code')
                ->where('webwork_code', 'REGEXP', 'RadioButtons\\s*\\(')
                ->get();
            echo count($questions) . "\r\n";
            $used_question_ids = DB::table('webwork_long_radio_buttons')
                ->select('question_id')
                ->get()
                ->pluck('question_id')
                ->toArray();
            foreach ($questions as $question) {
                $result = $this->hasLongRadioButtonLabel($question->webwork_code, 200);
                if ($result) {
                    if (!in_array($question->id, $used_question_ids)) {
                        echo $question->id . "\r\n";
                        $old_webwork_code = $question->webwork_code;
                        $new_webwork_code = $this->updateMaxLabelSize($old_webwork_code);
                        DB::table('webwork_long_radio_buttons')->insert([
                            'question_id' => $question->id,
                            'old_webwork_code' => $old_webwork_code,
                            'new_webwork_code' => $new_webwork_code,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $used_question_ids[] = $question->id;
                    }
                }
            }

            DB::commit();
            $radioButtons = DB::table('webwork_long_radio_buttons')->get();

            foreach ($radioButtons as $item) {
                if (preg_match('/RadioButtons\s*\((.*?)\);/s', $item->new_webwork_code, $match)) {
                    Log::info($item->id . ' $mc = RadioButtons(' . $match[1] . ");");
                }
            }
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }

    function hasLongRadioButtonLabel($code, $lengthThreshold = 200)
    {
        // Step 1: Extract full RadioButtons(...) call
        $pos = strpos($code, 'RadioButtons(');
        if ($pos === false) return false;

        $start = $pos + strlen('RadioButtons(');
        $depth = 1;
        $len = strlen($code);
        $end = $start;

        while ($end < $len && $depth > 0) {
            $char = $code[$end];
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            }
            $end++;
        }

        if ($depth !== 0) return false;

        $inner = substr($code, $start, $end - $start - 1);

        // Step 2: Find first array (assumed to be in square brackets)
        $arrayStart = strpos($inner, '[');
        if ($arrayStart === false) return false;

        $depth = 1;
        $i = $arrayStart + 1;
        $arrayLen = strlen($inner);
        while ($i < $arrayLen && $depth > 0) {
            $char = $inner[$i];
            if ($char === '[') {
                $depth++;
            } elseif ($char === ']') {
                $depth--;
            }
            $i++;
        }

        if ($depth !== 0) return false;

        $arrayString = substr($inner, $arrayStart, $i - $arrayStart); // includes []

        // Step 3: Extract quoted string elements using regex
        preg_match_all('/"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/', $arrayString, $matches);

        if (!isset($matches[1])) return false;

        $longElements = array_filter($matches[1], function ($el) use ($lengthThreshold) {
            return mb_strlen($el) > $lengthThreshold;
        });

        return !empty($longElements) ? $longElements : false;
    }

    function extractRadioButtonsArgs($code)
    {
        $pos = strpos($code, 'RadioButtons(');
        if ($pos === false) return null;

        $start = $pos + strlen('RadioButtons(');
        $depth = 1;
        $len = strlen($code);
        $end = $start;

        while ($end < $len && $depth > 0) {
            $char = $code[$end];
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            }
            $end++;
        }

        if ($depth === 0) {
            return substr($code, $pos, $end - $pos); // includes 'RadioButtons(...)'
        }
        return null; // no matching paren found
    }

    function updateMaxLabelSize($code)
    {
        $fullCall = $this->extractRadioButtonsArgs($code);
        if (!$fullCall) return $code; // no match

        // Extract the inner args inside parentheses
        $inner = substr($fullCall, strlen('RadioButtons('), -1);

        if (preg_match('/maxLabelSize\s*=>\s*\d+/', $inner)) {
            // Replace existing maxLabelSize value
            $inner = preg_replace('/maxLabelSize\s*=>\s*\d+/', 'maxLabelSize => 1000', $inner);
        } else {
            // Add maxLabelSize => 1000 before the closing
            $inner = rtrim($inner, ", \n\t");
            if (!empty($inner)) {
                $inner .= ', maxLabelSize => 1000';
            } else {
                $inner = 'maxLabelSize => 1000';
            }
        }

        $replacement = "RadioButtons(" . $inner . ")";

        // Replace in the original code
        return str_replace($fullCall, $replacement, $code);
    }

}
