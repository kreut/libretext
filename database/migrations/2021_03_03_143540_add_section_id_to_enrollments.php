<?php

use App\Course;
use App\Section;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class AddSectionIdToEnrollments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedBigInteger('section_id')->after('user_id');
        });

        Artisan::call('create:sections');

        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreign('section_id')->references('id')->on('sections');
            $table->unique(['user_id','section_id']);
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign('enrollments_section_id_foreign');
            $table->dropColumn('section_id');
        });
    }
}
