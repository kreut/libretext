<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateResetNodeAfterIncorrectAttemptToAssignmentTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignment_templates', function (Blueprint $table) {
            $table->unsignedTinyInteger('reset_node_after_incorrect_attempt')
                ->after('min_number_of_minutes_in_exposition_node')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignment_templates', function (Blueprint $table) {
            $table->dropColumn('nreset_node_after_incorrect_attempt');
        });
    }
}
