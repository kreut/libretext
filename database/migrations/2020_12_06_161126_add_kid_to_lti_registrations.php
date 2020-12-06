<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKidToLtiRegistrations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lti_registrations', function (Blueprint $table) {
            $table->string('kid')->after('key_set_url');
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
            $table->dropColumn('kid');
        });
    }
}
