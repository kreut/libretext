<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAutoProvisionedMessageToLtiNamesAndRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lti_names_and_roles', function (Blueprint $table) {
            $table->string('auto_provisioned_message')->nullable()->after('emailed_about_account');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lti_names_and_roles', function (Blueprint $table) {
            $table->dropColumn('auto_provisioned_message');
        });
    }
}
