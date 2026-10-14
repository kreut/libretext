<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Files (PDF, image, Word, Excel, PowerPoint) that students attach to a Discuss-it comment, up to 5 per comment.
 * No foreign keys: rows are cleaned up by the DiscussionComment "deleting" event.
 */
class CreateDiscussionCommentAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('discussion_comment_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('discussion_comment_id')->index('dca_discussion_comment_id_index');
            $table->string('file');
            $table->string('original_filename');
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
        Schema::dropIfExists('discussion_comment_attachments');
    }
}
