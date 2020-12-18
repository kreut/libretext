<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddLatePolicyColumnsToAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('late_policy', 12)->after('submission_files')->nullable()->comment('not accepted, marked late, deduction');
            $table->unsignedSmallInteger('late_deduction_percent')->after('late_policy')->nullable();
            $table->string('late_deduction_application_period', 25)->after('late_deduction_percent')->nullable()->comment('once or x time intervals');
        });
        DB::table('assignments')->update(['late_policy' => 'marked late']);
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['late_policy', 'late_deduction_percent','late_deduction_application_period']);
        });
    }
}
