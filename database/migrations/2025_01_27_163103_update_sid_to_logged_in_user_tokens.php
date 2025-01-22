<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSidToLoggedInUserTokens extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('logged_in_user_tokens', function (Blueprint $table) {
            $table->string('sid')->after('token');
        });
        Schema::table('logged_in_user_tokens', function (Blueprint $table) {
            $table->dropColumn('token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('logged_in_user_tokens', function (Blueprint $table) {
            $table->dropColumn('sid');
        });
    }
}
