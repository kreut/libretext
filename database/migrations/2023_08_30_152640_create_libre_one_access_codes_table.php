<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLibreOneAccessCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('libre_one_access_codes', function (Blueprint $table) {
            $table->id();
            $table->string('access_code');
            $table->string('email');
            $table->string('role');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->string('status');
            $table->timestamps();
            $table->foreign('course_id')->references('id')->on('courses');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('libre_one_access_codes');
    }
}
