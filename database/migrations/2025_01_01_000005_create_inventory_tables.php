<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('category', 20)->default('thuc_an');  // thuc_an | thuoc | vaccine | vat_tu | khac
            $table->string('unit', 20)->default('kg');
            $table->decimal('current_stock', 14, 3)->default(0);
            $table->decimal('avg_cost', 14, 2)->default(0);       // giá vốn bình quân gia quyền / đơn vị
            $table->decimal('low_stock_threshold', 14, 3)->default(0);
            $table->string('note')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['farm_id', 'category']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('type', 10);                           // in | out | adjust
            $table->decimal('quantity', 14, 3);                   // có dấu: in +, out −, adjust ±
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->decimal('total_cost', 16, 2)->default(0);     // luôn dương
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('event_id')->nullable();
            $table->string('supplier', 120)->nullable();
            $table->string('note')->nullable();
            $table->dateTime('occurred_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['item_id', 'occurred_at']);
            $table->index('event_id');
            $table->index(['farm_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_items');
    }
};
