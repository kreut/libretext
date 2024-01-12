<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCanvasUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('canvas_updates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->tinyInteger('updated_points')->default(0);
            $table->tinyInteger('updated_everybodys')->default(0);
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
        Schema::dropIfExists('canvas_updates');
    }
}
