<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCanvasMaxAttemptsErrorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('canvas_max_attempts_errors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id')->unique();
            $table->unsignedTinyInteger('sent_email')->default(0);
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
        Schema::dropIfExists('canvas_max_attempts_errors');
    }
}
