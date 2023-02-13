<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateItemsToRemovePlainTextOptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('remove_plain_text_options', function (Blueprint $table) {
            $table->string('default_open_ended_submission_type')->after('table_name')->nullable();
            $table->string('default_open_ended_text_editor')->after('table_name')->nullable();
            $table->string('open_ended_text_editor')->after('table_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('remove_plain_text_options', function (Blueprint $table) {
            //
        });
    }
}
