<?php

namespace App\Console\Commands\OneTimers;

use App\Assignment;
use App\AssignToTiming;
use App\BetaAssignment;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class deleteTareaAlphaBetaAssignments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:tareaAlphaBetaAssignments';

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
    public function handle(): int
    {
        try {
            $alpha_assignments_to_delete = [91719,91850,91845];
            DB::beginTransaction();
            echo Assignment::count();
            echo "\r\n";
            foreach ($alpha_assignments_to_delete as $alpha_assignment_id){
                $beta_assignments = BetaAssignment::where('alpha_assignment_id', $alpha_assignment_id)->get();
                $deleted_count = count($beta_assignments);
                foreach ($beta_assignments as $beta_assignment){
                    $assignment_to_delete = Assignment::find($beta_assignment->id);
                    $beta_assignment->delete();
                    $assignment_to_delete->removeAllAssociatedInformation(new AssignToTiming());
                }
                echo "$alpha_assignment_id deleted $deleted_count beta assignments\r\n";
                $alpha_assignment = Assignment::find($alpha_assignment_id);
                $alpha_assignment->removeAllAssociatedInformation(new AssignToTiming());
            }

            DB::commit();
            echo "Done!\r\n";
            echo Assignment::count();
        } catch (Exception $e){
            DB::rollback();
            echo $e->getMessage();
            return 1;
        }
        return 0;
    }
}
