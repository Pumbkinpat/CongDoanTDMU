<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('article_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->onDelete('cascade');
            $table->unsignedInteger('version_number')->default(1);
            $table->string('title');
            $table->longText('content');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('change_type')->default('EDITOR_EDIT');
            $table->boolean('is_ai_generated')->default(false);
            $table->string('ai_provider')->nullable();
            $table->string('ai_model')->nullable();
            $table->text('ai_prompt')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('article_versions');
    }
};