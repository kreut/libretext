<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQtiImports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qti_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('directory');
            $table->string('filename');
            $table->text('xml');
            $table->string('status', 20)->default('processing');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
            $table->unique(['user_id','directory','filename']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('qti_imports');
    }
}
