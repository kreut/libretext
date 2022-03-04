<?php

namespace App\Console\Commands\SearchImprovementFixes\DuplicateFixes\DeleteExtras;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class deleteDuplicates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:duplicates';

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
        $question_ids = DB::table('deleted_questions')
            ->select('id')
            ->pluck('id')
            ->toArray();
        DB::table('seeds')
            ->whereIn('question_id', $question_ids)
            ->delete();

        DB::table('question_tag')
            ->whereIn('question_id', $question_ids)
            ->delete();

        DB::table('questions')
            ->whereIn('id', $question_ids)
            ->delete();
        return 0;
    }
}
