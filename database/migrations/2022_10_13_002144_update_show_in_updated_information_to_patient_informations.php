<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateShowInUpdatedInformationToPatientInformations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patient_informations', function (Blueprint $table) {
            $table->unsignedTinyInteger('show_in_updated_information')
                ->after('updated_bmi')
                ->default(0);
            $table->dropColumn('first_application_of_updated_information');
        });

        Schema::table('patient_informations', function (Blueprint $table) {
            $table->unsignedTinyInteger('first_application_of_updated_information')
                ->after('updated_bmi')
                ->nullable();
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
            $table->dropColumn('show_in_updated_information');
        });
    }
}
