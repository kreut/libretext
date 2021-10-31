<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLtiSchoolsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lti_schools', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lti_registration_id');
            $table->unsignedBigInteger('school_id');
            $table->timestamps();
            $table->unique(['lti_registration_id','school_id']);
            $table->foreign('lti_registration_id')->references('id')->on('lti_registrations');
            $table->foreign('school_id')->references('id')->on('schools');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lti_schools');
    }
}
