<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->string('extension')->nullable();
            $table->unsignedBigInteger('size');
            $table->string('hash', 64)->unique();
            $table->string('download_token', 64)->unique();
            $table->text('description')->nullable();
            $table->string('password')->nullable();
            $table->string('uploader_ip', 45);
            $table->string('uploader_email')->nullable();
            $table->string('recipient_email')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('uploader_ip');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
