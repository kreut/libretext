<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOidcToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('users', function (Blueprint $table) {
            $table->string('central_identity_id')->after('id')->nullable();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('verify_status')->after('lms_user_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('central_identity_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('verify_status');
        });
    }
}
