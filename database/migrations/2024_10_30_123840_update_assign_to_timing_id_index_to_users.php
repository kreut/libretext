<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAssignToTimingIdIndexToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assign_to_users', function (Blueprint $table) {
            $table->index('assign_to_timing_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assign_to_users', function (Blueprint $table) {
            $table->dropIndex('assign_to_users_assign_to_timing_id_index');
        });
    }
}
