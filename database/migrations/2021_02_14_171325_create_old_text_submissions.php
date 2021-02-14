<?php

use App\SubmissionFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateOldTextSubmissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('old_text_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('question_id');
            $table->text('submission');
            $table->timestamps();
        });
        $text_submissions = SubmissionFile::where('type', 'text')->get();
        DB::beginTransaction();
        foreach ($text_submissions as $text_submission) {
            DB::table('old_text_submissions')->insert(
                ['user_id' => $text_submission->user_id,
                    'assignment_id' => $text_submission->assignment_id,
                    'question_id' => $text_submission->question_id,
                    'submission' => $text_submission->submission]
            );
        }
        DB::commit();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('old_text_submissions');
    }
}
