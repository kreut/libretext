<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateQuestionRevisionIdToWebworkAttachments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('webwork_attachments', function (Blueprint $table) {
            $table->unsignedBigInteger('question_revision_id')
                ->after('question_id')
                ->default(0);

            $table->dropUnique('webwork_attachments_filename_question_id_unique');
            $table->unique(['filename','question_id','question_revision_id'],'unique_by_question_id_revision_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('webwork_attachments', function (Blueprint $table) {
            $table->dropColumn('question_revision_id');
        });
    }
}
