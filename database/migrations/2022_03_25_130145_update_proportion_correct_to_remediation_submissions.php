<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateProportionCorrectToRemediationSubmissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('remediation_submissions', function (Blueprint $table) {
            $table->decimal('proportion_correct', 8, 4)->nullable()->change();
            $table->unsignedSmallInteger('submission_count')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('remediation_submissions', function (Blueprint $table) {
            //
        });
    }
}
