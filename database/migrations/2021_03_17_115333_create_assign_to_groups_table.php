<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssignToGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assign_to_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assign_to_timing_id');
            $table->string('group',10);
           $table->unsignedBigInteger('group_id');
            $table->timestamps();
            $table->foreign('assign_to_timing_id')->references('id')->on('assign_to_timings');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assign_to_groups');
    }
}
