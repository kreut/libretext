<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateHashToMacroAndMacroRevisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('webwork_macros', function (Blueprint $table) {
         $table->string('hash')->after('macro');
        });
        Schema::table('webwork_macro_revisions', function (Blueprint $table) {
            $table->string('hash')->after('macro');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('webwork_macros', function (Blueprint $table) {
            $table->dropColumn('hash');
        });
        Schema::table('webwork_macro_revisions', function (Blueprint $table) {
            $table->dropColumn('hash');
        });
    }
}
