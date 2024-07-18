<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSatisfiesRequirementToDiscussionComments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('discussion_comments', function (Blueprint $table) {
            $table->boolean('satisfied_requirement')->default(0)->after('file');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('discussion_comments', function (Blueprint $table) {
            $table->dropColumn('satisfied_requirement');
        });
    }
}
