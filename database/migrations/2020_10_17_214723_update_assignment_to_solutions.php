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
        Schema::table('solutions', function (Blueprint $table) {
            $table->dropForeign('solutions_question_id_foreign');
            $table->unsignedBigInteger('assignment_id')->after('id')->nullable();
            $table->unsignedBigInteger('question_id')->nullable()->change();
            $table->string('type')
                ->after('id')
                ->nullable(false)
                ->comment('q=question, a=assignment');

        });

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
            $table->dropColumn('type');
        });


    }
}
