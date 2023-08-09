<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDescriptionAndNotesToLearningTrees extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('learning_trees', function (Blueprint $table) {
            $table->string('description',2000)->change();
        });
        Schema::table('learning_trees', function (Blueprint $table) {
            $table->string('notes',5000)->after('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('learning_trees', function (Blueprint $table) {
           $table->dropColumn('notes');
        });
    }
}
