<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateShowCaptionsToQuestionMediaUploads extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('question_media_uploads', function (Blueprint $table) {
            $table->unsignedTinyInteger('show_captions')
                ->after('transcript')
                ->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('question_media_uploads', function (Blueprint $table) {
          $table->dropColumn('show_captions');
        });
    }

}
