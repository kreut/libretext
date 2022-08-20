<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateQuestionIdToH5pTypeAndSourceUrls extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('h5p_type_and_source_urls', function (Blueprint $table) {
            $table->dropForeign('h5p_type_and_source_urls_question_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('h5p_type_and_source_urls', function (Blueprint $table) {
            //
        });
    }
}
