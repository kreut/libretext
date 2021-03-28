<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPrimaryKeyToLtiDeployments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lti_deployments', function (Blueprint $table) {
           //needed when importing a current database
            $keys = DB::select(DB::raw(
                'SHOW KEYS
                        FROM lti_deployments
                        WHERE Key_name="PRIMARY"'));
            if (!$keys) {
                $table->string('id')->primary()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lti_deployments', function (Blueprint $table) {
            //
        });
    }
}
