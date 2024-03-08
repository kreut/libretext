<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateLaunchIdIndexToLtiLaunches extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lti_launches', function (Blueprint $table) {
            $table->index('launch_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lti_launches', function (Blueprint $table) {
        $table->dropIndex('lti_launches_launch_id_index');
        });
    }
}
