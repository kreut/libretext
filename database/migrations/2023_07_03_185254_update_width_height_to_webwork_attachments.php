<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateWidthHeightToWebworkAttachments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('webwork_attachments', function (Blueprint $table) {
            $table->unsignedSmallInteger('height')->after('filename')->nullable();
            $table->unsignedSmallInteger('width')->after('filename')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('webwork_attachments', function (Blueprint $table) {
         $table->dropColumn(['height','width']);
        });
    }
}
