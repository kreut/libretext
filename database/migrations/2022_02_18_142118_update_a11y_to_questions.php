<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateA11yToQuestions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('a11y_question');
            $table->string('a11y_technology',15)
                ->after('technology_id')
                ->nullable();
            $table->string('a11y_technology_id',191)
                ->after('a11y_technology')
                ->nullable();
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
            $table->string('a11y_question')->after('text_question');
            $table->dropColumn(['a11y_technology', 'a11y_technology_id']);
        });
    }
}
