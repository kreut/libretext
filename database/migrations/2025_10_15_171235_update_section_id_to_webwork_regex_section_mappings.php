<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSectionIdToWebworkRegexSectionMappings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('webwork_regex_section_mappings', function (Blueprint $table) {
            $table->unsignedBigInteger('section_id')->after('question_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('webwork_regex_section_mappings', function (Blueprint $table) {
            $table->dropColumn('section_id');
        });
    }
}
