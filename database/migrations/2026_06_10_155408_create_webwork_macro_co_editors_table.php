<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebworkMacroCoEditorsTable extends Migration
{
    public function up(): void
    {
        Schema::create('webwork_macro_co_editors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('webwork_macro_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['webwork_macro_id', 'user_id']);

            $table->foreign('webwork_macro_id')->references('id')->on('webwork_macros');

            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webwork_macro_co_editors');
    }
}
