<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateH5pTypeIds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('h5p_type_ids', function (Blueprint $table) {
            $table->unsignedBigInteger('question_id')->unique();
            $table->unsignedSmallInteger('type_id');
            $table->unsignedBigInteger('technology_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('h5p_type_ids');
    }
}
