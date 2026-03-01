<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained()->cascadeOnDelete();
            $table->string('ip', 45);
            $table->string('user_agent', 512)->nullable();
            $table->string('referer', 512)->nullable();
            $table->string('country', 2)->nullable();
            $table->timestamps();

            $table->index('ip');
            $table->index('file_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downloads');
    }
};
