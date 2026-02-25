<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCloudflareStreamVideosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cloudflare_stream_videos', function (Blueprint $table) {
            $table->id();
            $table->string('s3_path')->unique();
            $table->string('cloudflare_uid')->nullable();
            $table->unsignedInteger('times_served')->default(0);
            $table->string('captions_hash')->nullable();
            $table->enum('status', ['pending', 'processing', 'ready', 'error'])->default('pending');
            $table->string('error_message')->nullable();
            $table->boolean('marked_for_deletion')->default(0);
            $table->timestamps();
            $table->index('cloudflare_uid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cloudflare_stream_videos');
    }
}
