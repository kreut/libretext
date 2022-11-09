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
            $select_choices = Question::where('qti_json', 'LIKE', '%"questionType":"select_choice"%')
                ->get();


            foreach ($select_choices as $select_choice) {
                $already_added_to_database = DB::table('qti_json_before_fixing_select_choices')
                    ->where('question_id', $select_choice->id)
                    ->first();
                if (!$already_added_to_database) {
                    DB::table('qti_json_before_fixing_select_choices')
                        ->insert([
                            'question_id' => $select_choice->id,
                            'qti_json' => $select_choice->qti_json,
                            'created_at' => now(),
                            'updated_at' => now()]);
                    $qti_json = json_decode($select_choice->qti_json, 5);
                    foreach ($qti_json['inline_choice_interactions'] as $interaction_key => $interaction) {
                        foreach ($interaction as $item_key => $item) {
                            $qti_json['inline_choice_interactions'][$interaction_key][$item_key]['correctResponse'] = $item_key === 0;
                        }
                    }
                    $select_choice->qti_json = json_encode($qti_json);
                    $select_choice->save();
                    echo $select_choice->id . "\r\n";
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
