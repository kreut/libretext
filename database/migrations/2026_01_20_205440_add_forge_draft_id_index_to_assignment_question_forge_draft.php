<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForgeDraftIdIndexToAssignmentQuestionForgeDraft extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignment_question_forge_draft', function (Blueprint $table) {
            $table->index('forge_draft_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignment_question_forge_draft', function (Blueprint $table) {
            $table->dropIndex(['forge_draft_id']);
        });
    }
}

