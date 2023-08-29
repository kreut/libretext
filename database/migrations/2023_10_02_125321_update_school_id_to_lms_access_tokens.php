<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSchoolIdToLmsAccessTokens extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('lms_access_tokens', 'school_id')) {
            Schema::table('lms_access_tokens', function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->after('user_id');
                $table->foreign('school_id')->references('id')->on('schools');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('lms_access_tokens', function (Blueprint $table) {
            $table->dropForeign('lms_access_tokens_school_id_foreign');
        });
        Schema::table('lms_access_tokens', function (Blueprint $table) {
            $table->dropColumn('school_id');
        });
    }
}
