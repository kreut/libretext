<?php

namespace App\Console\Commands\SearchImprovementFixes\DuplicateFixes\Versions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use function dd;

class fixMultipleVersionsByInAssignment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:MultipleVersionsByInAssignment';

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
        $multiple_in_assignments = DB::table('questions')
            ->join('assignment_question', 'questions.id', '=', 'assignment_question.question_id')
            ->select('technology', 'technology_id')
            ->where('version', '>', 1)
            ->groupBy('technology','technology_id')
            ->get();

        foreach ($multiple_in_assignments as $multiple){
            $question_ids = DB::table('questions')
                ->where('technology', $multiple->technology)
                ->where('technology_id', $multiple->technology_id)
                ->select('id')
                ->get()
                ->pluck('id')
                ->toArray();
            $multiple->question_ids = $question_ids;
        }
        dd($multiple_in_assignments[500]);


        return 0;
    }
}
