<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mỗi Farm (trại) CHÍNH LÀ 1 tenant — mọi bảng nghiệp vụ đều có farm_id.
 * Cùng mô hình với Location bên Rót.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('font_scale', 10)->default('md');
            $table->string('accent_color', 20)->default('emerald');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('farm_user', function (Blueprint $table) {
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['farm_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_user');
        Schema::dropIfExists('farms');
    }
};
