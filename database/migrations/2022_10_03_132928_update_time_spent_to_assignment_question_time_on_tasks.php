<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTimeSpentToAssignmentQuestionTimeOnTasks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignment_question_time_on_tasks', function (Blueprint $table) {
        $table->renameColumn('time_spent','time_on_task');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignment_question_time_on_tasks', function (Blueprint $table) {
            //
        });
    }
}
