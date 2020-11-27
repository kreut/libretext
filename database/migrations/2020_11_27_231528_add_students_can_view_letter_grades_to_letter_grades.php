<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStudentsCanViewLetterGradesToLetterGrades extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('letter_grades', function (Blueprint $table) {
           $table->boolean('letter_grades_released')
               ->after('round_scores')
               ->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('letter_grades', function (Blueprint $table) {
            $table->dropColumn('letter_grades_released');
        });
    }
}
