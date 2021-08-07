<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTogglesToAssignmentQuestion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignment_question', function (Blueprint $table) {
            $table->boolean('assignment_information_shown_in_iframe')->after('points')->default(0);
            $table->boolean('submission_information_shown_in_iframe')->after('assignment_information_shown_in_iframe')->default(0);
            $table->boolean('attribution_information_shown_in_iframe')->after('submission_information_shown_in_iframe')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignment_question', function (Blueprint $table) {
            $table->dropColumn(['assignment_information_shown_in_iframe','submission_information_shown_in_iframe','attribution_information_shown_in_iframe']);
        });
    }
}
