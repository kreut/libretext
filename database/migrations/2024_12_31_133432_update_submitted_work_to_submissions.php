<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSubmittedWorkToSubmissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->timestamp('submitted_work_at')->after('submission')->nullable();
            $table->string('submitted_work')->after('submission')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('submitted_work');
            $table->dropColumn('submitted_work_at');
        });
    }
}
