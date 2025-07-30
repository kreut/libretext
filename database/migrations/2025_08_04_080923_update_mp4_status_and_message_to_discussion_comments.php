<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMp4StatusAndMessageToDiscussionComments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('discussion_comments', function (Blueprint $table) {
            $table->string('mp4_status')
                ->after('satisfied_requirement')
                ->nullable();
        });
        Schema::table('discussion_comments', function (Blueprint $table) {
            $table->text('mp4_message')
                ->after('mp4_status')
                ->nullable();
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
            $table->dropColumn('mp4_status');
            $table->dropColumn('mp4_message');
        });
    }
}
