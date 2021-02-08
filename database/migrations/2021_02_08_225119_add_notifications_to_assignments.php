<?php

use App\Assignment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddNotificationsToAssignments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('assignments', function (Blueprint $table) {
            $table->boolean('notifications')->after('include_in_weighted_average');
        });

        $assignments=DB::table('assignments')->join('assignment_groups', 'assignments.assignment_group_id', '=', 'assignment_groups.id')
            ->whereIn('assignment_groups.assignment_group', ['Exam', 'Final', 'Midterm'])
            ->select('assignments.id')
            ->get()
            ->pluck('id')
            ->toArray();

        Assignment::whereNotIn('id',$assignments)->update(['notifications'=>1]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('notifications');
        });
    }
}
