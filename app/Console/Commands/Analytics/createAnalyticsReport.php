<?php

namespace App\Console\Commands\Analytics;

use App\DataShop;
use App\Question;
use App\User;
use Exception;
use Illuminate\Console\Command;

class createAnalyticsReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:analyticsReport';

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
            $results = Question::selectRaw('year(created_at) year, monthname(created_at) month, count(*) AS num_questions')
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->get();
            $this->createReport($results, 'num_questions', 'Number Of Questions');

            $results = User::selectRaw('year(created_at) year, monthname(created_at) month, count(*) AS num_students')
                ->where('role',3)
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->get();
            $this->createReport($results, 'num_students', 'Number Of Students Registering');

            $results = User::selectRaw('year(created_at) year, monthname(created_at) month, count(*) AS num_graders')
                ->where('role',5)
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->get();
            $this->createReport($results, 'num_graders', 'Number Of Graders Registering');

            $results = Datashop::selectRaw('year(submission_time) year, monthname(submission_time) month, count(*) AS num_submissions')
                ->whereNotNull('submission_time')
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->get();
            $this->createReport($results, 'num_submissions', 'Number Of Auto-graded submissions');
            return 0;
        } catch (Exception $e){
            echo $e->getMessage();
            return 1;
        }
    }

    public function createReport($results, $field, $header)
    {
        $csv = fopen("/Users/franciscaparedes/Downloads/$header.csv", 'w');
        $columns = ['Year', 'Month', $header];
        fputcsv($csv, $columns);
        foreach ($results as $result) {
            fputcsv($csv, [$result->year, $result->month, $result->{$field}]);
        }
        fclose($csv);
        echo "$header written to file.\r\n";
    }
}
