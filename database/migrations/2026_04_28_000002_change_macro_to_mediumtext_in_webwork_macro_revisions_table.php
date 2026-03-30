<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeMacroToMediumtextInWebworkMacroRevisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('webwork_macro_revisions', function (Blueprint $table) {
            $table->mediumText('macro')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('webwork_macro_revisions', function (Blueprint $table) {
            $table->text('macro')->change();
        });
    }
}
