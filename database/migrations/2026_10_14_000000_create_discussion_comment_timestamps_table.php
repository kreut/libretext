<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Timestamps that a Discuss-it reply points to in an audio/video comment in the same thread.
 * No foreign keys: rows are cleaned up by the DiscussionComment "deleting" event.
 */
class CreateDiscussionCommentTimestampsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('discussion_comment_timestamps', function (Blueprint $table) {
            $table->id();
            //explicit index names: the generated ones would exceed MySQL's 64-character limit
            $table->unsignedBigInteger('discussion_comment_id')->index('dct_discussion_comment_id_index');
            $table->unsignedBigInteger('referenced_discussion_comment_id')->index('dct_referenced_discussion_comment_id_index');
            $table->decimal('seconds', 10, 2);
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('discussion_comment_timestamps');
    }
}
