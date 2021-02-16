<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOpenEndedTextEditorToAssignmentQuestion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignment_question', function (Blueprint $table) {
            $table->string('open_ended_text_editor', 5)
                ->after('open_ended_submission_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignment_question', function (Blueprint $table) {
            $table->dropColumn('open_ended_text_editor');
        });
    }
}
