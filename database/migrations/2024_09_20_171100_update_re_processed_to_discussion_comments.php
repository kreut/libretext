<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateReProcessedToDiscussionComments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('discussion_comments', function (Blueprint $table) {
            $table->tinyInteger('re_processed_transcript')->after('transcript')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('discussion_comments', function (Blueprint $table) {
            $table->dropColumn('re_processed_transcript');
        });
    }
}
