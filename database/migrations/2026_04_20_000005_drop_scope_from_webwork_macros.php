<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropScopeFromWebworkMacros extends Migration
{
    public function up()
    {
        Schema::table('webwork_macros', function (Blueprint $table) {
            $table->dropColumn('scope');
        });
    }

    public function down()
    {
        Schema::table('webwork_macros', function (Blueprint $table) {
            $table->enum('scope', ['local', 'global'])->default('local')->after('user_id');
        });
    }
}
