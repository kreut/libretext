<?php

namespace App\Console\Commands\SearchImprovementFixes\DuplicateFixes\Versions;

use App\Question;
use App\QuestionVersion;
use Exception;
use Illuminate\Console\Command;

class updateVersionToQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:VersionToQuestions';

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
    public function handle(QuestionVersion $questionVersion, Question $question)
    {
        try {
            $questionVersions = $questionVersion->get();
            foreach ($questionVersions as $questionVersion) {
                if ($questionVersion->version > 1) {
                    $question->where('id', $questionVersion->question_id)
                        ->update(['version' => $questionVersion->version]);
                }
            }
            return 0;
        } catch (Exception $e){
            echo $e->getMessage();
        }
    }
}
