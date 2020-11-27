<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoundScoresToLetterGradesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('letter_grades', function (Blueprint $table) {
            $table->Boolean('round_scores')->after('letter_grades')->default(false);
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
            $table->dropColumn('round_scores');
        });
    }
}
