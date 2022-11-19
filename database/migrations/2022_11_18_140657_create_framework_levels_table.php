<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFrameworkLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('framework_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('framework_id');
            $table->unsignedTinyInteger('level');
            $table->string('title',1000);
            $table->string('description',1000)->nullable();
            $table->unsignedTinyInteger('order');
            $table->unsignedBigInteger('parent_id')->default(0);
            $table->timestamps();
            $table->foreign('framework_id')->references('id')->on('frameworks');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('framework_levels');
    }
}
