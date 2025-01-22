<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateLoggedOutToLoggedInUserTokens extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('logged_in_user_tokens', function (Blueprint $table) {
            $table->dropColumn('logged_in_at');
            $table->unsignedTinyInteger('logged_out')->after('token')->default(0);
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
            //
        });
    }
}
