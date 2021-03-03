<?php

use App\Section;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DropCourseAccessCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('course_access_codes', function (Blueprint $table) {
            $course_access_codes = DB::table('course_access_codes')->get();
            foreach ($course_access_codes as $course_access_code) {
                Section::where('course_id', $course_access_code->course_id)
                    ->update(['access_code' => $course_access_code->access_code]);
                $course_access_code->access_code;
            }
            $table->drop();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
