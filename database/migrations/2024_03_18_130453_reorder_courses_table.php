<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReorderCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Step 1: Create new columns at the end of the table
        Schema::table('courses', function (Blueprint $table) {
            $table->timestamp('new_created_at')->nullable();
            $table->timestamp('new_updated_at')->nullable();
        });

        // Step 2: Copy data from the existing columns to the new columns
        DB::table('courses')->update([
            'new_created_at' => DB::raw('created_at'),
            'new_updated_at' => DB::raw('updated_at'),
        ]);

        // Step 3: Drop the existing columns
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('created_at');
            $table->dropColumn('updated_at');
        });

        // Step 4: Rename the new columns to match the original column names
        Schema::table('courses', function (Blueprint $table) {
            $table->renameColumn('new_created_at', 'created_at');
            $table->renameColumn('new_updated_at', 'updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
