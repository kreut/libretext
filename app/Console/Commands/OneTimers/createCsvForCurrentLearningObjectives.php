<?php

namespace App\Console\Commands\OneTimers;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class createCsvForCurrentLearningObjectives extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:csvForCurrentLearningObjectives {subject}';

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
        $subject = $this->argument('subject');
        try {
            $learning_outcomes = DB::table('learning_outcomes')
                ->where('subject', $subject)
                ->get();


            $fp = fopen("/Users/franciscaparedes/downloads/$subject.csv", 'w');
            fputcsv($fp, ['Level 1', 'Level 2', 'Level 3', 'Level 4','Descriptor']);
            foreach ($learning_outcomes as $learning_outcome) {
                $fields = [$learning_outcome->topic, '', '', '',$learning_outcome->description];
                fputcsv($fp, $fields);
            }
            fclose($fp);
            echo "CSV written.";

        } catch (Exception $e) {
            echo $e->getMessage();
        }

        return 0;
    }
}
