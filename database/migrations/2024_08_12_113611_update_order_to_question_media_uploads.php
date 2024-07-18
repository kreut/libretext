<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOrderToQuestionMediaUploads extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('question_media_uploads', function (Blueprint $table) {
          $table->unsignedSmallInteger('order')->after('s3_key')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('question_media_uploads', function (Blueprint $table) {
         $table->dropColumn('order');
        });
    }
}
