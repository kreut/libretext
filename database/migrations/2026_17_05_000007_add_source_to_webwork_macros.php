<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSourceToWebworkMacros extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('webwork_macros', 'source')) {
            Schema::table('webwork_macros', function (Blueprint $table) {
                $table->string('source')
                    ->default('custom')
                    ->after('user_id');
            });
        } else {
            Schema::table('webwork_macros', function (Blueprint $table) {
                $table->string('source')
                    ->default('standard')
                    ->change();
            });
        }
    }

    public function down()
    {
        Schema::table('webwork_macros', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
}
