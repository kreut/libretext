<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRubricCategoryCriteriaPendingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rubric_category_criteria_pendings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('rubric_category_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('notify_user_id');
            $table->unsignedTinyInteger('processed')->default(0);
            $table->timestamps();
            $table->foreign('assignment_id')->references('id')->on('assignments');
            $table->foreign('rubric_category_id')->references('id')->on('rubric_categories');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('notify_user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rubric_category_criteria_pendings');
    }
}
