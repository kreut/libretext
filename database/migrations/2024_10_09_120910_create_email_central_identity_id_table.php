<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailCentralIdentityIdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_central_identity_id', function (Blueprint $table) {
            $table->id();
            $table->string('central_identity_id');
            $table->string('email');
            $table->timestamps();
            $table->unique(['central_identity_id','email']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('email_central_identity_id');
    }
}
