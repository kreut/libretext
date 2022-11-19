<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFrameworkLevelFrameworkDescriptor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('framework_level_framework_descriptor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('framework_level_id');
            $table->unsignedBigInteger('framework_descriptor_id');
            $table->timestamps();
            $table->unique(['framework_level_id','framework_descriptor_id'],'unique_framework_level_framework_descriptor');
            $table->foreign('framework_level_id','framework_level_foreign')->references('id')->on('framework_levels');
            $table->foreign('framework_descriptor_id','framework_descriptor_foreign')->references('id')->on('framework_descriptors');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('framework_level_framework_descriptor');
    }
}
