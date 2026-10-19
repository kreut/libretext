<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubmissionFilePageTables extends Migration
{
    /**
     * Page images are generated once per uploaded PDF, not per question: a compiled PDF (type 'a') and every
     * type 'q' row that points at it share the same `submission` filename, so they share one set of images.
     *
     * @return void
     */
    public function up()
    {
        // One row per uploaded PDF: tracks the conversion itself.
        Schema::create('submission_file_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->string('submission'); // filename within assignments/{assignment_id}/
            $table->string('status', 20)->default('pending'); // pending | processing | completed | failed
            $table->unsignedSmallInteger('number_of_pages')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['assignment_id', 'submission']);
            $table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('cascade');
        });

        // One row per rendered page.
        Schema::create('submission_file_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->string('submission');
            $table->unsignedSmallInteger('page');
            $table->string('s3_key');
            // natural pixel size: needed later so annotation coordinates map correctly at any zoom
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->timestamps();

            $table->unique(['assignment_id', 'submission', 'page']);
            $table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('cascade');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('submission_file_pages');
        Schema::dropIfExists('submission_file_conversions');
    }
}
