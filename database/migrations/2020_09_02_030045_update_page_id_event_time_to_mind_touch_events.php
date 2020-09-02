<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePageIdEventTimeToMindTouchEvents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mind_touch_events', function (Blueprint $table) {
            $table->unique(['page_id', 'event_time']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mind_touch_events', function (Blueprint $table) {
            $table->dropIndex(['page_id', 'event_time']);
        });
    }
}
