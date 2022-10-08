<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFirstApplicationOfUpdatedInformationToPatientInformations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patient_informations', function (Blueprint $table) {
            $table->unsignedTinyInteger('first_application_of_updated_information')->nullable();
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
