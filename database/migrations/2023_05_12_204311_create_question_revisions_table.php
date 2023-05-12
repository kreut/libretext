<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateQuestionRevisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        DB::statement('CREATE TABLE question_revisions LIKE questions');

        Schema::table('question_revisions', function (Blueprint $table) {
            $table->unsignedBigInteger('question_id')->after('id');
            $table->foreign('question_id')->references('id')->on('questions');
            $table->dropIndex('questions_page_id_library_copy_source_id_unique');

        });

        Schema::table('question_revisions', function (Blueprint $table) {
            $table->unsignedSmallInteger('revision_number')->after('id');
            $table->unique(['question_id','revision_number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('question_revisions');
    }
}
