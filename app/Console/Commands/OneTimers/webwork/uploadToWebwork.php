<?php

namespace App\Console\Commands\OneTimers\webwork;

use App\Helpers\Helper;
use App\Question;
use App\Webwork;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class uploadToWebwork extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upload:toWebwork {limit}';

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
     * @param Webwork $webwork
     * @return int
     */
    public function handle(Webwork $webwork): int
    {
        $limit = $this->argument('limit');
        $questions = Question::whereNotNull('webwork_code')->limit($limit)->get();
        $time = microtime(true);
        $question_ids = DB::table('old_webwork_paths')
            ->select('question_id')
            ->pluck('question_id')
            ->toArray();
        foreach ($questions as $question) {
            if (!in_array($question->id, $question_ids)) {
                try {

                    $message = 'success';
                    $response = $webwork->storeQuestion($question->webwork_code,$question->id);

                    if ($response !== 200) {
                        throw new Exception ($response);
                    }
                    DB::beginTransaction();

                    DB::table('old_webwork_paths')->insert([
                        'question_id' => $question->id,
                        'technology_id' => $question->technology_id ?: 'new question',
                        'created_at' => now(),
                        'updated_at' => now()]);
                    $question->updateWebworkPath();
                    DB::commit();
                } catch (Exception $e) {
                    DB::rollback();
                    $message = $e->getMessage();
                }
                echo "$question->id $message\r\n";
            }
        }
        echo microtime(true) - $time . ' seconds';

        return 0;
    }
}
