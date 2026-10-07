<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * File Upload Mode is no longer an assignment property: every delayed assignment lets students upload a file
 * per question or upload one PDF for multiple questions and assign its pages.
 *
 * The original values are saved in file_upload_mode_backups, then the assignments are converted to 'both'.
 * To put them back: php artisan assignments:restore-file-upload-modes
 */
class ConvertFileUploadModesToBoth extends Migration
{
    /**
     * @return void
     */
    public function up()
    {
        Schema::create('file_upload_mode_backups', function (Blueprint $table) {
            $table->unsignedBigInteger('assignment_id')->primary();
            $table->string('file_upload_mode');
        });

        DB::transaction(function () {
            DB::table('file_upload_mode_backups')->insertUsing(['assignment_id', 'file_upload_mode'],
                DB::table('assignments')
                    ->select('id', 'file_upload_mode')
                    ->whereIn('file_upload_mode', ['compiled_pdf', 'individual_assessment']));

            DB::table('assignments')
                ->whereIn('file_upload_mode', ['compiled_pdf', 'individual_assessment'])
                ->update(['file_upload_mode' => 'both']);
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Artisan::call('assignments:restore-file-upload-modes');
        Schema::dropIfExists('file_upload_mode_backups');
    }
}
