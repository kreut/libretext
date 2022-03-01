<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateLibraryPageIdToQuestionTitles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('question_titles', function (Blueprint $table) {
            $table->string('library');
            $table->string('page_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('question_titles', function (Blueprint $table) {
            $table->dropColumn(['library','page_id']);
        });
    }
}
