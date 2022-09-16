<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTimesToDataShops extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('data_shops', function (Blueprint $table) {
            $table->timestamp('review_time_start')->after('time')->nullable();
            $table->timestamp('review_time_end')->after('review_time_start')->nullable();
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
