<?php

use App\School;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSchoolIdToCourses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('courses', function (Blueprint $table) {
            $school = School::where('name', 'Not Specified')->first();
            $table->unsignedBigInteger('school_id')
                ->after('public')
                ->default($school->id);
            $table->foreign('school_id')
                ->references('id')
                ->on('schools');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('courses', function (Blueprint $table) {
        });
    }
}
