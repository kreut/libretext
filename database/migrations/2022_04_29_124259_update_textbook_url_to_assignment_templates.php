<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTextbookUrlToAssignmentTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignment_templates', function (Blueprint $table) {
            $table->renameColumn('libretexts_url','textbook_url');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignment_templates', function (Blueprint $table) {
            $table->renameColumn('textbook_url','libretexts_url');
        });
    }
}
