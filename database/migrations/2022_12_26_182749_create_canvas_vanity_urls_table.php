<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCanvasVanityUrlsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('canvas_vanity_urls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lti_registration_id');
            $table->string('vanity_url');
            $table->timestamps();
            $table->foreign('lti_registration_id')->references('id')->on('lti_registrations');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('canvas_vanity_urls');
    }
}
