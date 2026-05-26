<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateExternalSourcePointsToAssignmentProperties extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->unsignedSmallInteger('external_source_points')->change();
        });

        Schema::table('assignment_templates', function (Blueprint $table) {
            $table->unsignedSmallInteger('external_source_points')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->unsignedTinyInteger('external_source_points')->change();
        });

        Schema::table('assignment_templates', function (Blueprint $table) {
            $table->unsignedTinyInteger('external_source_points')->change();
        });
    }
}
