<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RenameTechnologyIdColumnToQuestions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection()->getDoctrineSchemaManager()
                        ->getDatabasePlatform()
                        ->registerDoctrineTypeMapping('enum', 'string');
        Schema::table('questions', function (Blueprint $table) {
            $table->renameColumn('technology_id','page_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->renameColumn('page_id','technology_id');
        });
    }
}
