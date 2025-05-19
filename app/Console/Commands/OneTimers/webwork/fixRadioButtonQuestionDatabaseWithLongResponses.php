<?php

namespace App\Console\Commands\OneTimers\webwork;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixRadioButtonQuestionDatabaseWithLongResponses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:RadioButtonQuestionDatabaseWithLongResponses';

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
            $long_radio_button_questions = DB::table('webwork_long_radio_buttons')
                ->select('question_id')->get()
                ->pluck('question_id')
                ->toArray();

            $latest_revisions = DB::table('question_revisions as qr1')
                ->whereRaw('qr1.revision_number = (
        SELECT MAX(qr2.revision_number)
        FROM question_revisions qr2
        WHERE qr2.question_id = qr1.question_id
    )')->whereIn('question_id', $long_radio_button_questions)
                ->get();
            $num_updated = 0;
            foreach ($latest_revisions as $latest_revision) {
                $new_webwork_code = DB::table('webwork_long_radio_buttons')
                    ->where('question_id', $latest_revision->question_id)
                    ->first()
                    ->new_webwork_code;
                DB::table('question_revisions')
                    ->where('id', $latest_revision->id)
                    ->update(['webwork_code' => $new_webwork_code,
                        'updated_at' => now()]);
            }
            foreach ($long_radio_button_questions as $long_radio_button_question_id) {
                $new_webwork_code = DB::table('webwork_long_radio_buttons')
                    ->where('question_id',  $long_radio_button_question_id)
                    ->first()
                    ->new_webwork_code;
                $num_updated += DB::table('questions')
                    ->where('id',  $long_radio_button_question_id)
                    ->update(['webwork_code' => $new_webwork_code,
                        'updated_at' => now()]);

            }
            echo "Num updated: $num_updated";
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
