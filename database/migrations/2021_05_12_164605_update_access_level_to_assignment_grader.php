<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAccessLevelToAssignmentGrader extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignment_grader', function (Blueprint $table) {
            $table->boolean('access_level')
                ->after('user_id')
                ->comment('1 = Full access, 0 = No access');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignment_grader', function (Blueprint $table) {
            $table->dropColumn('access_level');
        });
    }
}
