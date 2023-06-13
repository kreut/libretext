<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentEmailsWithSubmissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_emails_with_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('student_email');
            $table->string('student_name');
            $table->text('message');
            $table->string('instructor_email');
            $table->string('instructor_name');
            $table->string('status')->default('pending');
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
        Schema::dropIfExists('student_emails_with_submissions');
    }
}
