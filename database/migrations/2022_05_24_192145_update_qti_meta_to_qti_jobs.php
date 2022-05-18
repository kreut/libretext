<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateQtiMetaToQtiJobs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qti_jobs', function (Blueprint $table) {
            $table->string('qti_source')->after('user_id');
            $table->unsignedBigInteger('course_id')->after('qti_source')->nullable();
            $table->unsignedBigInteger('assignment_template_id')->after('course_id')->nullable();
            $table->unsignedTinyInteger('public')->after('assignment_template_id');
            $table->unsignedBigInteger('folder_id')->after('public');
            $table->string('license')->after('folder_id');
            $table->string('license_version')->after('license')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qti_jobs', function (Blueprint $table) {
            //
        });
    }
}
