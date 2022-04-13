<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAssignmentTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        DB::statement('CREATE TABLE assignment_templates LIKE assignments;');
        Schema::table('assignment_templates', function (Blueprint $table) {
            $table->dropColumn(['course_id','name']);
            $table->unsignedBigInteger('user_id')->after('id');
            $table->string('template_name')->after('user_id');
            $table->string('template_description')->after('template_name');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assignment_templates');
    }
}
