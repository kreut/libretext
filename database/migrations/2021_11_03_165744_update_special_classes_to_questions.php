<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSpecialClassesToQuestions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->text('text_question')->after('non_technology')->nullable();
            $table->text('a11y_question')->after('text_question')->nullable();
            $table->text('solution_html')->after('a11y_question')->nullable();
            $table->text('hint')->after('solution_html')->nullable();
            $table->string('libretexts_link', 500)->after('hint')->nullable();
            $table->text('notes')->after('libretexts_link')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['text_question','a11y_question','solution_html','hint','libretexts_link','notes']);
        });
    }
}
