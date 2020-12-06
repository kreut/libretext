<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLaunchAndAssignmentIdToLaunches extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lti_launches', function (Blueprint $table) {
            $table->unsignedBigInteger('assignment_id')->after('user_id');
            $table->text('launch');
            $table->foreign('assignment_id')->on('assignments')->references('id');
            $table->unique(['user_id', 'assignment_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lti_launches', function (Blueprint $table) {
            $table->dropColumn(['launch', 'assignment_id']);
        });
    }
}
