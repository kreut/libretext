<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFormatToSubmittedWorks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('submitted_works', function (Blueprint $table) {
            $table->string('format')->after('submitted_work');
            $table->unique(['user_id', 'assignment_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('submitted_works', function (Blueprint $table) {
            $table->dropColumn('format');
            $table->dropIndex('submitted_works_user_id_assignment_id_question_id_unique');
        });
    }
}
