<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePendingTranscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pending_transcriptions', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('language');
            $table->string('upload_type');
            $table->string('environment');
            $table->string('status')->nullable();
            $table->string('message',10000)->nullable();
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
        Schema::dropIfExists('pending_transcriptions');
    }
}
