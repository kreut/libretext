<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAssignmentIdToAdaptMigrations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('adapt_migrations', function (Blueprint $table) {
            $table->unsignedBigInteger('assignment_id')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('adapt_migrations', function (Blueprint $table) {
          $table->dropColumn('assignment_id');
        });
    }
}
