<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateQuestionRevisionIdToQuestionMediaUploads extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('question_media_uploads', function (Blueprint $table) {
            $table->unsignedBigInteger('question_revision_id')->after('question_id')->default(0);
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
            $table->dropColumn('question_revision_id');
        });
    }
}
