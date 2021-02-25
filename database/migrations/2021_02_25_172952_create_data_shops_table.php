<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDataShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data_shops', function (Blueprint $table) {
            //https://pslcdatashop.web.cmu.edu/help?page=importFormatTd
            $table->id();
            $table->string('anon_student_id',55);
            $table->string('session_id',255);
            $table->timestamp('time');
            $table->string('level', 100);
            $table->string('problem_name',255);
            $table->string('problem_view',255);
            $table->string('outcome',30)->comment('CORRECT, INCORRECT, HINT');
            $table->string('input',255);
            $table->string('school',100);
            $table->string('class', 100);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('data_shops');
    }
}
