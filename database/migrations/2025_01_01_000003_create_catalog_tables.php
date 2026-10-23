<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Danh mục: loài (species), giống (breeds), khu vực/chuồng (zones). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('species', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('emoji', 16)->default('🐾');
            $table->string('group', 20)->default('khac');      // gia_suc | gia_cam | thuy_san | thu_cung | khac
            $table->string('sale_unit', 6)->default('kg');     // đơn vị bán mặc định: kg | con
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('farm_id');
        });

        Schema::create('breeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('species_id')->constrained('species')->cascadeOnDelete();
            $table->string('name', 80);
            $table->timestamps();

            $table->index(['farm_id', 'species_id']);
        });

        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->unsignedInteger('capacity')->nullable();
            $table->string('note')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('farm_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zones');
        Schema::dropIfExists('breeds');
        Schema::dropIfExists('species');
    }
};
