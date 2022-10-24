<?php

namespace App\Console\Commands\Migrations;

use App\Question;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class unmigrateAllQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unmigrate:allQuestions';

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
            echo "Start\r\n";
            $start = microtime(true);
            $mass_migrations = DB::table('adapt_mass_migrations')->where('migrated', 1)->limit(25000)->get();
            $num_questions = count($mass_migrations);
            DB::beginTransaction();
            foreach ($mass_migrations as $mass_migration) {
                Question::where('id', $mass_migration->new_page_id)
                    ->update(['library' => $mass_migration->original_library, 'page_id' => $mass_migration->original_page_id]);
                DB::table('adapt_mass_migrations')->where('id', $mass_migration->id)->update(['migrated' => 0]);
            }
            DB::commit();
            $seconds = microtime(true) - $start;
            echo "Unmigrated $num_questions. Finished in $seconds seconds.";
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        return 0;
    }
}
