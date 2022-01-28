<?php

namespace App\Console\Commands;

use App\Assignment;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class scaleAssignmentWithNewTotalPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scale:AssignmentWithNewTotalPoints';

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
        $total_points = 5;
        try {
            DB::beginTransaction();
            foreach ([3443, 3445] as $assignment_id) {
                $assignment = Assignment::find($assignment_id);
                $tables_columns = [
                    ['table' => 'assignment_question', 'column' => 'points'],
                    ['table' => 'submissions', 'column' => 'score'],
                    ['table' => 'submission_files', 'column' => 'score'],
                    ['table' => 'scores', 'column' => 'score']
                ];
                foreach ($tables_columns as $value) {
                    $table = $value['table'];
                    $column = $value['column'];
                    $rows = DB::table($table)->where('assignment_id', $assignment_id)->get();

                    foreach ($rows as $row) {
                        DB::table($table)->where('id', $row->id)
                            ->update([$column =>$row->{$column} * (   $total_points  / 790)]);

                    }
                }
            }
            DB::commit();
            return 0;
        } catch (Exception $e){
            echo $e->getMessage();
            DB::rollback();
        }
        return 1;
    }
}
