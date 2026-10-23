<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Event log — nguồn sự thật cho timeline, số lượng, chi phí vật tư và báo cáo.
 * quantity_delta: thay đổi số lượng con (+/−). cost: chi phí gắn với event
 * (vật tư tiêu hao theo giá vốn lúc đó, hoặc tiền con giống nhập thêm).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->dateTime('occurred_at');
            $table->integer('quantity_delta')->default(0);
            $table->decimal('weight_kg', 10, 3)->nullable();      // cân: TB kg/con
            $table->foreignId('item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->decimal('item_quantity', 14, 3)->nullable();
            $table->decimal('cost', 16, 2)->default(0);
            $table->string('cost_category', 20)->nullable();      // con_giong | thuc_an | thuoc | vat_tu | khac
            $table->foreignId('related_batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->string('group_key', 36)->nullable()->index();  // ghép cặp chuyển đàn đi/đến
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['batch_id', 'occurred_at']);
            $table->index(['farm_id', 'occurred_at']);
            $table->index(['farm_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
