<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebworkPrivateToNativesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('webwork_private_to_natives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->string('original_path',1000);
            $table->text('webwork_code')->nullable();
            $table->string('status',1000);
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
        Schema::dropIfExists('webwork_private_to_natives');
    }
}
