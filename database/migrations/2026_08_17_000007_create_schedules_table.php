<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->onDelete('cascade');
            $table->dateTime('publish_at');
            $table->boolean('publish_to_web')->default(true);
            $table->boolean('publish_to_facebook')->default(false);
            $table->enum('status', ['pending', 'executed', 'failed'])->default('pending');
            $table->text('log_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('schedules');
    }
};
