<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientInformationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patient_informations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->string('name');
            $table->string('code_status');
            $table->string('gender');
            $table->string('allergies');
            $table->string('dob');
            $table->string('age');
            $table->string('weight');
            $table->string('bmi');
            $table->timestamps();
            $table->unique('assignment_id');
            $table->foreign('assignment_id')->references('id')->on('assignments');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patient_informations');
    }
}
