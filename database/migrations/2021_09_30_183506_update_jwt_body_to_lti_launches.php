<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateJwtBodyToLtiLaunches extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lti_launches', function (Blueprint $table) {
            $table->text('jwt_body')->after('launch_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lti_launches', function (Blueprint $table) {
            $table->dropColumn('jwt_body');
        });
    }
}
