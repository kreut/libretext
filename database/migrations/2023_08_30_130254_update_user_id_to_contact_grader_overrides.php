<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUserIdToContactGraderOverrides extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contact_grader_overrides', function (Blueprint $table) {
           $table->dropForeign('contact_grader_overrides_user_id_foreign');
        });
        Schema::table('contact_grader_overrides', function (Blueprint $table) {
            $table->bigInteger('user_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contact_grader_overrides', function (Blueprint $table) {
            //
        });
    }
}
