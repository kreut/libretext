<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddScopeToWebworkMacros extends Migration
{
    public function up()
    {
        Schema::table('webwork_macros', function (Blueprint $table) {
            // Make user_id nullable so promoted global macros have no owner
            $table->unsignedBigInteger('user_id')->nullable()->change();
            // Add scope column
            $table->enum('scope', ['local', 'global'])->default('local')->after('user_id');
        });
    }

    public function down()
    {
        Schema::table('webwork_macros', function (Blueprint $table) {
            $table->dropColumn('scope');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
}
