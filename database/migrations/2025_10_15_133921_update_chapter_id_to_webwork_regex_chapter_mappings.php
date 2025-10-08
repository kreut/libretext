<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateChapterIdToWebworkRegexChapterMappings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('webwork_regex_chapter_mappings', function (Blueprint $table) {
            $table->unsignedBigInteger('chapter_id')->after('question_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('webwork_regex_chapter_mappings', function (Blueprint $table) {
            $table->dropColumn('chapter_id');
        });
    }
}
