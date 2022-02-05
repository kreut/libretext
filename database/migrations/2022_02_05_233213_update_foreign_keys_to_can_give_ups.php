<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateForeignKeysToCanGiveUps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('can_give_ups', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('assignment_id')->references('id')->on('assignments');
            $table->foreign('question_id')->references('id')->on('questions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('can_give_ups', function (Blueprint $table) {
            $table->dropForeign('can_give_ups_user_id_foreign');
            $table->dropForeign('can_give_ups_assignment_id_foreign');
            $table->dropForeign('can_give_ups_question_id_foreign');
        });
    }
}
