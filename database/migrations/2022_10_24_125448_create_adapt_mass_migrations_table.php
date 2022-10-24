<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdaptMassMigrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adapt_mass_migrations', function (Blueprint $table) {
            $table->id();
            $table->string('original_library');
            $table->unsignedBigInteger('original_page_id');
            $table->unsignedBigInteger('new_page_id')->comment('same as question id');
            $table->unsignedTinyInteger('migrated')->default(0);
            $table->timestamps();
            $table->unique('new_page_id');
            $table->foreign('new_page_id')->references('id')->on('questions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('adapt_mass_migrations');
    }
}
