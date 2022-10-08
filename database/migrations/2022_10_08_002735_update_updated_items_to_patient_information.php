<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUpdatedItemsToPatientInformation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patient_informations', function (Blueprint $table) {
            $table->string('updated_weight')->after('weight')->nullable();
            $table->string('updated_bmi')->after('bmi')->nullable();
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
