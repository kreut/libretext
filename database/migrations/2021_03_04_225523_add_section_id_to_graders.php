<?php

use App\Grader;
use App\Section;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSectionIdToGraders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $graders = Grader::all();
        Grader::truncate();
        Schema::table('graders', function (Blueprint $table) {
            $table->dropForeign('graders_course_id_foreign');
            $table->dropIndex('graders_course_id_user_id_unique');
            $table->dropColumn('course_id');
            $table->unsignedBigInteger('section_id')->after('id');
        });
        foreach ($graders as $grader) {
            $section = Section::where('course_id', $grader->course_id)->first();
            $section_grader = new Grader();
            $section_grader->user_id = $grader->user_id;
            $section_grader->section_id = $section['id'];
            $section_grader->save();
        }
        Schema::table('graders', function (Blueprint $table) {

            $table->unique(['user_id','section_id']);
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
        Schema::table('graders', function (Blueprint $table) {
            //
        });
    }
}
