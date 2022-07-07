<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdatePublicToLearningTrees extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('learning_trees', function (Blueprint $table) {
            $table->tinyInteger('public')->after('description');
        });

        DB::table('learning_trees')->update(['public' => 1]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('learning_trees', function (Blueprint $table) {
            $table->dropColumn('public');
        });
    }
}
