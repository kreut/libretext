<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdaptMigrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adapt_migrations', function (Blueprint $table) {
            $table->id();
            $table->string('original_library');
            $table->unsignedBigInteger('original_page_id');
            $table->unsignedBigInteger('new_page_id');
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
        Schema::dropIfExists('adapt_migrations');
    }
}
