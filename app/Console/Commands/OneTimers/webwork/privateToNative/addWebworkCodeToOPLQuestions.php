<?php

namespace App\Console\Commands\OneTimers\webwork\privateToNative;

use App\Question;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class addWebworkCodeToOPLQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:webworkCodeToOplQuestions';

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
            $opls = DB::table('opls')->where('status','pending')->get();
            foreach ($opls as $opl) {
                $question = Question::find($opl->question_id);
                if ($question) {
                    $question->technology_id = null;
                    $question->webwork_code = $opl->webwork_code;
                    $question->updated_at = now();
                    $question->save();
                    DB::table('opls')->where('id', $opl->id)->update(['status' => 'completed']);
                } else {
                    DB::table('opls')->where('id', $opl->id)->update(['status' => 'no question exists']);
                }
            }
            echo "Done!";
            return 0;
        } catch (Exception $e) {
            echo $e->getMessage();
            DB::rollback();
        }
    }
}
