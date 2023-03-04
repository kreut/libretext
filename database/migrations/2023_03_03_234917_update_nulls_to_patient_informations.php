<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateNullsToPatientInformations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patient_informations', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('code_status')->nullable()->change();
            $table->string('gender')->nullable()->change();
            $table->string('allergies')->nullable()->change();
            $table->string('dob')->nullable()->change();
            $table->string('age')->nullable()->change();
            $table->string('weight')->nullable()->change();
            $table->string('bmi')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('patient_informations', function (Blueprint $table) {
            //
        });
    }
}
