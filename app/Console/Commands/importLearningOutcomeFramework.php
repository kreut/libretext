<?php

namespace App\Console\Commands;

use App\LearningOutcome;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class importLearningOutcomeFramework extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:LearningOutcomeFramework';

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
            $subject = 'pre-calculus';
            $has_topics = $subject === 'pre-calculus';
            $csv = fopen("/Users/franciscaparedes/adapt_laravel_8/storage/app/learning_outcomes/$subject.csv",'r');
            //hopefully the file will be of a correct form!
            while (($item = fgetcsv($csv, 10000, ",")) !== FALSE) {
                $topic = '';
                if ($has_topics) {
                    $topic = trim($item[0]);
                    $description = trim($item[1]);
                } else {
                    $description = trim($item[0]);
                }
                if (!DB::table('learning_outcomes')
                    ->where('subject', $subject)
                    ->where('description', $description)
                    ->first()) {
                    $LearningOutcome = new LearningOutcome();
                    $LearningOutcome->topic = $topic;
                    $LearningOutcome->subject = $subject;
                    $LearningOutcome->description = $description;
                    $LearningOutcome->save();
                }
            }
            DB::commit();
            return 0;
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
            return 1;
        }
    }
}
