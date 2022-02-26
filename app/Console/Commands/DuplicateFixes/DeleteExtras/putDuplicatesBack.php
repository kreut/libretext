<?php

namespace App\Console\Commands\DuplicateFixes\DeleteExtras;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class putDuplicatesBack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'put:DuplicatesBack';

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
            $tables = ['questions', 'question_tag', 'seeds'];
            foreach ($tables as $table) {
                $deleteds = DB::table("deleted_$table")->get();
                foreach ($deleteds as $deleted) {
                    if (!(DB::table($table)->where('id', $deleted->id)->first())) {
                        DB::table($table)->insert(json_decode(json_encode($deleted), true));
                    }
                    DB::table("deleted_$table")->where('id', $deleted->id)->delete();
                }
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();


        }
        return 0;
    }
}
