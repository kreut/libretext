<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTextAndTypeToSubmissionFiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('submission_files', function (Blueprint $table) {
            $table->string('type',15)->comment('q=question file, audio, text')->change();
            $table->string('submission', 2500)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('submission_files', function (Blueprint $table) {
            //
        });
    }
}
