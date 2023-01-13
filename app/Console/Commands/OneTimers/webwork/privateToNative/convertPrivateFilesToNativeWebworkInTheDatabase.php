<?php

namespace App\Console\Commands\OneTimers\webwork\privateToNative;

use App\Helpers\Helper;
use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class convertPrivateFilesToNativeWebworkInTheDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'convert:PrivateFilesToNativeWebworkInTheDatabase';

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
            $private_questions = DB::table('webwork_private_to_natives')
                ->where('status', 'copied')
                ->get();

            foreach ($private_questions as $private_question) {
                $question = Question::find($private_question->question_id);
                $question->webwork_code = $private_question->webwork_code;
                $question->updateWebworkPath();
                echo $private_question->original_path . "\r\n";
            }
            DB::commit();

        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
            return 1;

        }
        return 0;
    }
}
