<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOtherItemsToDataShops extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('data_shops', function (Blueprint $table) {
            $table->renameColumn('question_view', 'submission_count');
        });

        Schema::table('data_shops', function (Blueprint $table) {
            $table->string('submission_count')->nullable()->change();
            $table->string('outcome')->nullable()->change();
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
