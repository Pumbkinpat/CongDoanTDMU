<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug');
            $table->integer('categoryId')->default(2);
            $table->string('categoryName')->default('Thông Báo Chỉ Đạo');
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->text('image')->nullable();
            $table->string('author')->default('Ban Thường Vụ TDMU');
            $table->integer('authorId')->default(1);
            $table->string('status')->default('published');
            $table->string('statusName')->default('Đã Xuất Bản');
            $table->boolean('isAiGenerated')->default(false);
            $table->text('aiPrompt')->nullable();
            $table->integer('viewsCount')->default(0);
            $table->integer('likesCount')->default(0);
            $table->integer('sharesCount')->default(0);
            $table->string('scheduledAt')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
