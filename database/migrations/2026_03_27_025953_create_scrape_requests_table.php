<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scrape_requests', function (Blueprint $table) {
            $table->id();
            $table->string('site');
            $table->json('parameters');
            $table->string('status')->default('pending');
            $table->unsignedInteger('requested_posts_count')->nullable();
            $table->unsignedInteger('discovered_posts_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index('site');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scrape_requests');
    }
};
