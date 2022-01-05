<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateTypeToSavedQuestionsFolders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('saved_questions_folders', function (Blueprint $table) {
            $table->string('type', 15)->after('name');
        });
        DB::table('saved_questions_folders')->update(['type'=>'my_favorites']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('saved_questions_folders', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
