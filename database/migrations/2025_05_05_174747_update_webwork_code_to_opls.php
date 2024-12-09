<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateWebworkCodeToOpls extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('opls', function (Blueprint $table) {
            $table->text('webwork_code')->after('webwork_path');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('opls', function (Blueprint $table) {
            $table->dropColumn('webwork_code');
        });
    }
}
