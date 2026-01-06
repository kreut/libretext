<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForgeSettingsToAssignmentQuestion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignment_question', function (Blueprint $table) {
           $table->json('forge_settings')->after('discuss_it_settings')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignment_question', function (Blueprint $table) {
           $table->dropColumn('forge_settings');
        });
    }
}
