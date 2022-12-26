<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNoUserLoggedInErrors extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('no_user_logged_in_errors', function (Blueprint $table) {
            $table->id();
            $table->text('file');
            $table->string('line', 1000);
            $table->string('method');
            $table->string('endpoint',1000);
            $table->string('request',10000);
            $table->string('ip',1000);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('no_user_logged_in_errors');
    }
}
