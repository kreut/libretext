<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateH5pAdaptStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('h5p_adapt_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('category');
            $table->string('adapt_status');
            $table->string('xapi');
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
        Schema::dropIfExists('h5p_adapt_statuses');
    }
}
