<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRubricCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rubric_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->string('category');
            $table->text('criteria');
            $table->unsignedSmallInteger('percent');
            $table->unsignedSmallInteger('order');
            $table->timestamps();
            $table->foreign('assignment_id')
                ->references('id')
                ->on('assignments');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rubric_categories');
    }
}
