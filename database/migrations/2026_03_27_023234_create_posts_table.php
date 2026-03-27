<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('source_site');
            $table->unsignedBigInteger('source_post_id');
            $table->string('md5', 32);
            $table->string('file_ext', 10)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('rating', 2)->nullable();
            $table->integer('score')->nullable();
            $table->string('author')->nullable();
            $table->timestamp('source_created_at')->nullable();
            $table->text('source_file_url');
            $table->text('source_preview_url')->nullable();
            $table->string('storage_disk')->default('local');
            $table->string('storage_path')->nullable();
            $table->string('download_status')->default('pending');
            $table->unsignedSmallInteger('download_attempts')->default(0);
            $table->timestamp('downloaded_at')->nullable();
            $table->text('last_download_error')->nullable();
            $table->json('source_payload')->nullable();

            $table->timestamps();

            $table->unique(['source_site', 'source_post_id']);
            $table->index('md5');
            $table->index('download_status');
            $table->index('rating');
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();

            $table->timestamps();

            $table->unique('name');
            $table->index('type');
        });

        Schema::create('post_tag', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();

            $table->unique(['post_id', 'tag_id']);
            $table->index(['tag_id', 'post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('posts');
    }
};
