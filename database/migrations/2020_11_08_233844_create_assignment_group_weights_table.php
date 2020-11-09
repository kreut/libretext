<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssignmentGroupWeightsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assignment_group_weights', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_group_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedTinyInteger('assignment_group_weight');

            $table->foreign('course_id')->references('id')->on('courses');
            $table->foreign('assignment_group_id')->references('id')->on('assignment_groups');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assignment_group_weights');
    }
}
