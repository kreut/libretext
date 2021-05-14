<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateGradingReminderTimePeriodToGraderNotifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('grader_notifications', function (Blueprint $table) {
            $table->dropColumn('grading_reminder_time_period');
            $table->unsignedSmallInteger('num_reminders_per_week')->after('for_late_submissions');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('grader_notifications', function (Blueprint $table) {

        });
    }
}
