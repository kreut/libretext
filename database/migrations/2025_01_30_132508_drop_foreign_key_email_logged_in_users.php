<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropForeignKeyEmailLoggedInUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('logged_in_user_tokens', function (Blueprint $table) {
           $table->dropForeign('logged_in_user_tokens_email_foreign');
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
