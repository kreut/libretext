<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAssignmentIdQuestionIdToDataShopEnrollments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('data_shops_enrollments', function (Blueprint $table) {
            $table->unsignedBigInteger('assignment_id')->default(0)->after('number_of_enrolled_students');
        });
        Schema::table('data_shops_enrollments', function (Blueprint $table) {
            $table->unsignedBigInteger('question_id')->default(0)->after('assignment_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('data_shops_enrollments', function (Blueprint $table) {
       $table->dropColumn('assignment_id');
            $table->dropColumn('question_id');
        });
    }
}
