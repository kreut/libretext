<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateHintsAndHintsPenaltyToAssignments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->boolean('hint')->after('number_of_allowed_attempts_penalty')->nullable()->comment('applies to non-delayed assessments');
            $table->unsignedSmallInteger('hint_penalty')->after('hint')->nullable()->comment('applies to non-delayed assessments');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['hint', 'hint_penalty']);
        });
    }
}
