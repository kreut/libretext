<?php

use App\Assignment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdatePurposeAndGradingStylesIdToQuestions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedSmallInteger('grading_style_id')->after('non_technology_html')->nullable();
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->text('purpose')->after('grading_style_id')->nullable();
        });
        Schema::table('questions', function (Blueprint $table) {
            $assignment = Assignment::find(19088);
            if ($assignment) {
                DB::table('questions')
                    ->where('id', 163524)
                    ->update(['purpose' => $assignment->purpose, 'grading_style_id' => $assignment->grading_style_id]);
            }
        });
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['purpose', 'grading_style_id']);
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
            //
        });
    }
}
