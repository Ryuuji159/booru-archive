<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->index(['download_status', 'id'], 'posts_download_status_id_index');
            $table->index(['download_status', 'download_attempts', 'id'], 'posts_download_status_attempts_id_index');
        });

        Schema::table('scrape_requests', function (Blueprint $table) {
            $table->index(['status', 'id'], 'scrape_requests_status_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('scrape_requests', function (Blueprint $table) {
            $table->dropIndex('scrape_requests_status_id_index');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_download_status_id_index');
            $table->dropIndex('posts_download_status_attempts_id_index');
        });
    }
};
