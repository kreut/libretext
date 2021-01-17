<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCanSubmitAndCanViewToAssignmentQuestionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignment_question', function (Blueprint $table) {
            $table->boolean('can_view')->after('points')->comment('Additional control for clicker type assessments');
            $table->boolean('can_submit')->after('can_view')->comment('Additional control for clicker type assessments');
        });

        DB::table('assignment_question')->update(['can_view'=>1, 'can_submit'=>1]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignment_question', function (Blueprint $table) {
            $table->dropColumn(['can_view', 'can_submit']);
        });
    }
}
