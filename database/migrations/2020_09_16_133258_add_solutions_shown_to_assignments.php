<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSolutionsShownToAssignments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->boolean('solutions_shown')->default(0)->after('submission_files');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('solutions_shown');
        });
    }
}
