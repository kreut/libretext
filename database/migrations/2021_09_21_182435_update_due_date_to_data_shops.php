<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDueDateToDataShops extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('data_shops', function (Blueprint $table) {
            $table->dateTime('due')->after('outcome')->nullable();
            $table->string('level_name')->after('level')->nullable();
            $table->string('level_group')->after('level_name')->nullable();
            $table->string('library')->after('problem_name')->nullable();
            $table->string('page_id')->after('library')->nullable();
            $table->string('number_of_attempts_allowed')->after('level_group')->nullable();
            $table->string('class_name')->after('class')->nullable();
            $table->string('class_start_date')->after('class_name')->nullable();
            $table->string('instructor_name')->after('class_name')->nullable();
            $table->string('instructor_email')->after('instructor_name')->nullable();
            $table->string('status')->after('instructor_email')->default('not fixed')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('data_shops', function (Blueprint $table) {
            $table->dropColumn(['due','level_name','level_group','library','page_id','number_of_attempts_allowed','class_name','class_start_date','instructor_name','instructor_email','status']);

        });
    }
}
