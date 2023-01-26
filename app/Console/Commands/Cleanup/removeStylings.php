<?php

namespace App\Console\Commands\Cleanup;

use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class removeStylings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:stylings';

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
            $questions_to_fix = Question::where('qti_json', 'LIKE', '%line-height%')
                ->get();
            DB::beginTransaction();
            $fixed_stylings = DB::table('fixed_stylings')
                ->get('question_id')
                ->pluck('question_id')
                ->toArray();
            $max_batch = DB::table('fixed_stylings')->max('batch');
            $batch = $max_batch ? $max_batch + 1 : 1;
            foreach ($questions_to_fix as $question_to_fix) {
                if (!in_array($question_to_fix->id, $fixed_stylings)) {
                    DB::table('fixed_stylings')->insert([
                        'batch' => $batch,
                        'question_id' => $question_to_fix->id,
                        'qti_json' => $question_to_fix->qti_json,
                        'non_technology_html' => $question_to_fix->non_technology_html,
                        'created_at'=> now(),
                        'updated_at' => now()
                    ]);
                    $fixed_stylings[] = $question_to_fix->id;
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
