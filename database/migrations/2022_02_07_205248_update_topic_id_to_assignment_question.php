<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTopicIdToAssignmentQuestion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignment_question', function (Blueprint $table) {
            $table->unsignedBigInteger('assignment_topic_id')->after('order')->nullable();
            $table->foreign('assignment_topic_id')->references('id')->on('assignment_topics');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignment_question', function (Blueprint $table) {
            $table->dropForeign('assignment_question_assignment_topic_id_foreign');
            $table->dropColumn('assignment_topic_id');
        });
    }
}
