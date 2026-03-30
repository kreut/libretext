<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebworkMacroRevisionsTable extends Migration
{
    public function up()
    {
        Schema::create('webwork_macro_revisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('webwork_macro_id');
            $table->string('name');
            $table->text('description');
            $table->text('macro');
            $table->unsignedBigInteger('edited_by_user_id');
            $table->unsignedInteger('revision_number')->default(0);
            $table->text('reason_for_edit')->nullable();
            $table->timestamps();

            $table->foreign('webwork_macro_id')
                  ->references('id')->on('webwork_macros')->onDelete('cascade');
            $table->foreign('edited_by_user_id')
                  ->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('webwork_macro_revisions');
    }
}
