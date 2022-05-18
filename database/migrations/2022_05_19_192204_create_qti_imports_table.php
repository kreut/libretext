<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQtiImportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('qti_imports');
        Schema::create('qti_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('qti_job_id');
            $table->string('identifier');
            $table->text('xml');
            $table->unsignedBigInteger('question_id')->nullable();
            $table->string('status', 2000)->default('processing');
            $table->timestamps();
            $table->foreign('qti_job_id')->references('id')->on('qti_jobs');
            $table->unique(['qti_job_id', 'identifier']);

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
