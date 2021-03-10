<?php

use App\Traits\SubmissionFiles;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddTextFeedbackEditorToSubmissionFiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('submission_files', function (Blueprint $table) {
           $table->string('text_feedback_editor',5)->after('text_feedback')->nullable();
        });
        DB::table('submission_files')->where('text_feedback', '<>',null)
                                        ->update(['text_feedback_editor' => 'plain']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('submission_files', function (Blueprint $table) {
            $table->dropColumn('text_feedback_editor');
        });
    }
}
