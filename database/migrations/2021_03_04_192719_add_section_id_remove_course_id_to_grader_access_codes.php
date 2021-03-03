<?php

use App\GraderAccessCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSectionIdRemoveCourseIdToGraderAccessCodes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        GraderAccessCode::truncate();
        Schema::table('grader_access_codes', function (Blueprint $table) {
            $table->dropForeign('ta_access_codes_course_id_foreign');
            $table->dropColumn('course_id');
            $table->unsignedBigInteger('section_id')->after('id');
            $table->foreign('section_id')->references('id')->on('sections');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('grader_access_codes', function (Blueprint $table) {
            //
        });
    }
}
