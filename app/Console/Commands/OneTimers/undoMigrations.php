<?php

namespace App\Console\Commands\OneTimers;

use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class undoMigrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'undo:migrations';

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
            $migrations = DB::table('adapt_migrations')->get();
            foreach ($migrations as $migration) {
                $question_id = $migration->new_page_id;
                $question = Question::find($question_id);
                $question->library = $migration->original_library;
                $question->page_id = $migration->original_page_id;
                $question->save();
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
