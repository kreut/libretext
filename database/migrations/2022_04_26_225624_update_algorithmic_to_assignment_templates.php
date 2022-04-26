<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAlgorithmicToAssignmentTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignment_templates', function (Blueprint $table) {
            $table->unsignedTinyInteger('algorithmic')
                ->after('hint_penalty')
                ->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignment_templates', function (Blueprint $table) {
            $table->dropColumn('algorithmic');
        });
    }
}
