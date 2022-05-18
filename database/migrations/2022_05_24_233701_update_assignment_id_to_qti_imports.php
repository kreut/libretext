<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAssignmentIdToQtiImports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qti_imports', function (Blueprint $table) {
            $table->unsignedBigInteger('assignment_id')->after('xml')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qti_imports', function (Blueprint $table) {
            $table->dropColumn('assignment_id');
        });
    }
}
