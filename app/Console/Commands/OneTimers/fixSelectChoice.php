<?php

namespace App\Console\Commands\OneTimers;

use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixSelectChoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:selectChoice';

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
            $no_correct_responses = Question::where('qti_json', 'LIKE', '%"questionType":"select_choice"%')
                ->where('qti_json', 'NOT LIKE', '%correctResponse%')
                ->get();
            /* $no_correct_responses = DB::table('questions')
                 ->join('users','questions.question_editor_user_id','=','users.id')
                 ->where('qti_json', 'LIKE', '%"questionType":"select_choice"%')
                 ->where('qti_json', 'NOT LIKE', '%correctResponse%')
                 ->select('email')
                 ->groupBy('email')
                 ->get();*/

            foreach ($no_correct_responses as $no_correct_response) {
                $already_added_to_database = DB::table('qti_json_before_fixing_select_choices')
                    ->where('question_id', $no_correct_response->question_id)
                    ->first();
                if (!$already_added_to_database) {
                    DB::table('qti_json_before_fixing_select_choices')
                        ->insert([
                            'question_id'=> $no_correct_response->id,
                            'qti_json'=> $no_correct_response->qti_json,
                            'created_at' => now(),
                            'updated_at'=> now()]);
                    $qti_json = json_decode($no_correct_response->qti_json, 5);
                    foreach ($qti_json['inline_choice_interactions'] as $interaction_key => $interaction) {
                        foreach ($interaction as $item_key => $item) {
                            $qti_json['inline_choice_interactions'][$interaction_key][$item_key]['correctResponse'] = $item_key === 0;
                        }
                    }
                    $no_correct_response->qti_json = json_encode($qti_json);
                    $no_correct_response->save();
                }
            }
            DB::commit();

        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
