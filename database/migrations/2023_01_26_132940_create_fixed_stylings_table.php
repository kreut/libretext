<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFixedStylingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fixed_stylings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('batch');
            $table->unsignedBigInteger('question_id');
            $table->text('non_technology_html')->nullable();
            $table->text('qti_json')->nullable();
            $table->timestamps();
            $table->unique('question_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fixed_stylings');
    }
}
