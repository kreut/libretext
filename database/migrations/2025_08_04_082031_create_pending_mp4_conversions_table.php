<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePendingMp4ConversionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pending_mp4_conversions', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('environment');
            $table->string('status');
            $table->text('message')->nullable();
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
        Schema::dropIfExists('pending_mp4_conversions');
    }
}
