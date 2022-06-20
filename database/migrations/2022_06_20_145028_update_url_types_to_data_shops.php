<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUrlTypesToDataShops extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('data_shops', function (Blueprint $table) {
            $table->string('textbook_url', 1000)
                ->after('class_name')
                ->nullable();
              $table->renameColumn('url', 'question_url');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('data_shops', function (Blueprint $table) {
            $table->renameColumn('question_url', 'url');
            $table->dropColumn('textbook_url');
        });
    }
}
