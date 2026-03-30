<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropHashFromMacroAndMacroRevisions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('webwork_macros', function (Blueprint $table) {
            $table->dropColumn('hash');
        });
        Schema::table('webwork_macro_revisions', function (Blueprint $table) {
            $table->dropColumn('hash');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
