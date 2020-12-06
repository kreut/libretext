<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLtiRegistrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lti_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('iss');
            $table->string('auth_login_url');
            $table->string('auth_token_url');
            $table->string('auth_server');
            $table->string('client_id');
            $table->string('key_set_url');
            $table->unsignedBigInteger('lti_key_id');
            $table->foreign('lti_key_id')->references('id')->on('lti_keys');
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
        Schema::dropIfExists('lti_registrations');
    }
}
