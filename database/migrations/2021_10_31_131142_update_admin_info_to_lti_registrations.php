<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateAdminInfoToLtiRegistrations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lti_registrations', function (Blueprint $table) {
            $table->string('admin_name')->after('campus_id');
            $table->string('admin_email')->after('admin_name');
            $table->boolean('active')->after('lti_key_id');
        });
        DB::table('lti_registrations')->update(['active'=>1]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lti_registrations', function (Blueprint $table) {
            $table->dropColumn(['admin_name','admin_email','active']);
        });
    }
}
