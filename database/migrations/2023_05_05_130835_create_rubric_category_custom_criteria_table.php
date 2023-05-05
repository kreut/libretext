<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRubricCategoryCustomCriteriaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rubric_category_custom_criteria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('rubric_category_id');
            $table->text('custom_criteria');
            $table->timestamps();
            $table->foreign('assignment_id','rccc-assignment_foreign_id')->references('id')->on('assignments');
            $table->foreign('rubric_category_id','rccc-rubric_category_foreign_id')->references('id')->on('rubric_categories');
        });



    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rubric_category_custom_criteria');
    }
}
