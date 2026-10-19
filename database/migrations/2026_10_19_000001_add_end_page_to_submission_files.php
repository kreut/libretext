<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEndPageToSubmissionFiles extends Migration
{
    /**
     * The last page (inclusive) of the compiled PDF that belongs to this question.
     * NULL for rows created before end pages existed; the grader falls back to the PDF view for those.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('submission_files', function (Blueprint $table) {
            $table->unsignedSmallInteger('end_page')->nullable()->after('page');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('submission_files', function (Blueprint $table) {
            $table->dropColumn('end_page');
        });
    }
}
