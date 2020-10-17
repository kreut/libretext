<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAssignmentToSolutions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::disableForeignKeyConstraints();
        Schema::table('solutions', function (Blueprint $table) {
            $table->unsignedBigInteger('assignment_id')->after('id');
            $table->unsignedBigInteger('question_id')->nullable()->change();
            $table->foreign('assignment_id')->references('id')->on('assignments');
            //can't have the uniqueness because the question_id might not exist
            //can't have the foreign key of the question_id because it might not exist

        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('solutions', function (Blueprint $table) {
            $table->unsignedBigInteger('question_id')->nullable(false)->change();
            $table->dropColumn('assignment_id');
        });
    }
}
