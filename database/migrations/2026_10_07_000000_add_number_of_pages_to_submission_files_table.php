<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The number of pages in a student's PDF for multiple questions (type 'a'), so that they can't assign a page past
 * the end of it.  Left empty for everything else.  PDFs uploaded before this column existed are counted the first
 * time a page is assigned.
 */
class AddNumberOfPagesToSubmissionFilesTable extends Migration
{
    /**
     * @return void
     */
    public function up()
    {
        Schema::table('submission_files', function (Blueprint $table) {
            $table->unsignedSmallInteger('number_of_pages')->nullable()->after('page');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('submission_files', function (Blueprint $table) {
            $table->dropColumn('number_of_pages');
        });
    }
}
