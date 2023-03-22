<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSchoolIndexToDataShops extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('data_shops', function (Blueprint $table) {
            Schema::table('data_shops', function (Blueprint $table) {
                $table->unsignedBigInteger('school')->change();
            });
            Schema::table('data_shops', function (Blueprint $table) {
                $table->index('school');
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('data_shops', function (Blueprint $table) {
            //
        });
    }
}
