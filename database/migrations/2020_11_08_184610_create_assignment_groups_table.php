<?php

use App\AssignmentGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAssignmentGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assignment_groups', function (Blueprint $table) {
            $table->id();
            $table->string('assignment_group');
            $table->unsignedBigInteger('user_id')->default(0);
            $table->unsignedBigInteger('course_id')->default(0);
            $table->timestamps();
        });
        $init_values = ['Homework', 'Lab', 'Exam', 'Midterm', 'Final', 'Quiz'];
        foreach ($init_values as $init_value) {
            $assignmentType = new AssignmentGroup();
            $assignmentType->assignment_group = $init_value;
            $assignmentType->save();
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assignment_groups');
    }
}
