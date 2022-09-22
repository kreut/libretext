<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAssignmentIdToTesterStudents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tester_students', function (Blueprint $table) {
          $table->unsignedBigInteger('assignment_id')->after('section_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tester_students', function (Blueprint $table) {
            $table->dropColumn('assignment_id');
        });
    }
}
