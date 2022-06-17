<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCorrectToH5pVideoInteractions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('h5p_video_interactions', function (Blueprint $table) {
            $table->unsignedTinyInteger('correct')->after('submission');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('h5p_video_interactions', function (Blueprint $table) {
          $table->dropColumn('correct');
        });
    }
}
