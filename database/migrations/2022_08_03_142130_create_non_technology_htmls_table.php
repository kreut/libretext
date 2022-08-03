<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNonTechnologyHtmlsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('non_technology_htmls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->mediumText('non_technology_html');
            $table->timestamps();
            $table->unique('question_id');
            $table->foreign('question_id')->references('id')->on('questions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('non_technology_htmls');
    }
}
