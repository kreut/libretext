<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdatePointsPerQuestionChangesToAssignments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->decimal('total_points')
                ->after('default_points_per_question')
                ->nullable();
            $table->decimal('default_points_per_question')
                ->nullable()
                ->change();
            $table->string('points_per_question', 30)
                ->nullable()
                ->after('scoring_type')
                ->comment('question weight/number of points');
        });
        DB::table('assignments')
            ->where('source', 'a')
            ->update(['points_per_question' => 'number of points']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['total_assignment_points', 'points_per_question']);
        });
    }
}
