<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaximumNumberOfAllowedAttemptsNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('maximum_number_of_allowed_attempts_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->string('status')->default('pending');
            $table->text('message')->nullable();
            $table->timestamps();
            $table->foreign('assignment_id', 'max_number_allowed_passbacks_foreign')
                ->references('id')
                ->on('assignments');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('maximum_number_of_allowed_attempts_notifications');
    }
}
