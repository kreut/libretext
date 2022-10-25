<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateQtiJsonTypeToQtiJsonBeforeFixingSelectChoices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('qti_json_before_fixing_select_choices', function (Blueprint $table) {
            $table->text('qti_json')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('qti_json_before_fixing_select_choices', function (Blueprint $table) {
            //
        });
    }
}
