<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeNumberOfAllowedAttempts1ToAssignments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('assignments')
            ->where('assessment_type', 'real time')
            ->where('scoring_type', 'p')
            ->update(['number_of_allowed_attempts' => 1]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

            DB::table('assignments')
                ->where('assessment_type', 'real time')
                ->where('scoring_type', 'p')
                ->update(['number_of_allowed_attempts' => null]);

    }
}
