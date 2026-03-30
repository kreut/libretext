<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsRetiredToWebworkMacros extends Migration
{
    public function up()
    {
        Schema::table('webwork_macros', function (Blueprint $table) {
            $table->boolean('is_retired')->default(false)->after('macro');
        });
    }

    public function down()
    {
        Schema::table('webwork_macros', function (Blueprint $table) {
            $table->dropColumn('is_retired');
        });
    }
}
