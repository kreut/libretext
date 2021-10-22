<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePointsAndScoringTypeToDataShops extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('data_shops', function (Blueprint $table) {
            $table->string('problem_points')->after('problem_name');
            $table->string('level_scoring_type')->after('level_group')->comment('p=performance,c=completion');
            $table->string('level_points')->after('level_scoring_type');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('data_shops', function (Blueprint $table) {
            $table->dropColumn(['problem_points','level_scoring_type','level_points']);
        });
    }
}
