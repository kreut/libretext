<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateIssAgainToLtiRegistrations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lti_registrations', function (Blueprint $table) {
            $table->renameColumn('iss','issuer');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lti_registrations', function (Blueprint $table) {
            $table->renameColumn('issuer','iss');
        });
    }
}
