<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateVttInformationToDiscussionComments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('discussion_comments', function (Blueprint $table) {
           $table->text('transcript')->nullable()->after('file');
        });
        Schema::table('discussion_comments', function (Blueprint $table) {
            $table->string('status')->nullable()->after('transcript');
        });
        Schema::table('discussion_comments', function (Blueprint $table) {
            $table->text('message')->nullable()->after('status');
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
           $table->dropColumn('transcript');
            $table->dropColumn('status');
            $table->dropColumn('message');
        });
    }
}
