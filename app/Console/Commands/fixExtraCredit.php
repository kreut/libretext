<?php

namespace App\Console\Commands;

use App\Assignment;
use App\AssignmentGroup;
use App\AssignmentGroupWeight;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixExtraCredit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:extraCredit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the extra credit field to be standard';

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
     * @return mixed
     */
    public function handle()
    {
        DB::beginTransaction();
        $new_extra_credit = AssignmentGroup::firstOrCreate([
            'assignment_group' => 'Extra Credit',
            'user_id' => 0,
            'course_id' => 0
        ]);

        $new_extra_credit_assignment_group_id = $new_extra_credit->id;
        echo "New extra credit assignment group id:  $new_extra_credit_assignment_group_id \r\n";
        $old_extra_credits = DB::table('assignment_groups')
            ->where('assignment_group', 'Extra Credit')
            ->where('user_id', '<>', 0)
            ->get();
        foreach ($old_extra_credits as $old_extra_credit_assignment_group) {
            echo "Old extra credit assignment group id: $old_extra_credit_assignment_group->id \r\n";
            AssignmentGroupWeight::where('assignment_group_id', $old_extra_credit_assignment_group->id)
                ->update(['assignment_group_id'=> $new_extra_credit_assignment_group_id]);
            $assignments = Assignment::where('assignment_group_id', $old_extra_credit_assignment_group->id)->get();
            foreach ( $assignments as $key=>$value){
                echo "Updating assignment $value->id \r\n";
            }
            Assignment::where('assignment_group_id', $old_extra_credit_assignment_group->id)
                ->update(['assignment_group_id'=> $new_extra_credit_assignment_group_id]);
            AssignmentGroup::where('id', $old_extra_credit_assignment_group->id)->delete();
        }

        DB::commit();

    }
}
