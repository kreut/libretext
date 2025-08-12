<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameOidcCredentialsTableToLibreoneCredentialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('oidc_credentials', function (Blueprint $table) {
            $table->rename('libreone_credentials');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('libreone_credentials', function (Blueprint $table) {
            $table->rename('oidc_credentials');
        });
    }
}
