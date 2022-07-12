<?php

use App\Question;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateQuestionLearningOutcomeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {

            Schema::create('question_learning_outcome', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('question_id');
                $table->unsignedBigInteger('learning_outcome_id');
                $table->foreign('question_id')->references('id')->on('questions');
                $table->foreign('learning_outcome_id')->references('id')->on('learning_outcomes');
                $table->unique(['question_id', 'learning_outcome_id'], 'question_learning_outcome_unique');
                $table->timestamps();
            });
            DB::beginTransaction();
            $questions = DB::table('questions')->whereNotNull('learning_outcome_id')->get();
            foreach ($questions as $question) {
                $data = ['question_id' => $question->id,
                    'learning_outcome_id' => $question->learning_outcome_id,
                    'created_at' => now(),
                    'updated_at' => now()];
                DB::table('question_learning_outcome')->insert($data);
            }
            DB::commit();
            Schema::table('questions', function (Blueprint $table) {
                $table->dropForeign('questions_learning_outcome_id_foreign');
                $table->dropColumn('learning_outcome_id');
            });
        } catch (Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('question_learning_outcome');
    }
}
