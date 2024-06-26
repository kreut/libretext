<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateReviewTimeSavedToReviewHistories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('review_histories', function (Blueprint $table) {

            $table->unsignedTinyInteger('saved_to_data_shops')->after('session_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('review_histories', function (Blueprint $table) {
            //
        });
    }
}
