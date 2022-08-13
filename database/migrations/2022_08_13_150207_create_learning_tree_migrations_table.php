<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLearningTreeMigrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('learning_tree_migrations', function (Blueprint $table) {
            $table->id();
            $table->string('original_library');
            $table->unsignedBigInteger('original_page_id');
            $table->unsignedBigInteger('new_page_id');
            $table->unsignedBigInteger('learning_tree_id');
            $table->string('email');
            $table->text('original_learning_tree');
            $table->text('migrated_learning_tree');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('learning_tree_migrations');
    }
}
