<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch = lứa nuôi (đàn) HOẶC 1 cá thể (kind = individual, dùng cho thú cưng /
 * con giống quý). Cùng 1 bảng để toàn bộ event/kho/chi phí/báo cáo dùng chung.
 * current_quantity là số CACHE, luôn tính lại từ initial_quantity + SUM(events.quantity_delta).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('species_id')->constrained('species')->cascadeOnDelete();
            $table->foreignId('breed_id')->nullable()->constrained('breeds')->nullOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->string('kind', 12)->default('batch');        // batch | individual
            $table->string('code', 30)->nullable();
            $table->string('name', 100);
            $table->string('gender', 10)->nullable();            // duc | cai | khong_ro
            $table->date('birth_date')->nullable();
            $table->date('started_on');
            $table->unsignedInteger('initial_quantity')->default(1);
            $table->integer('current_quantity')->default(0);
            $table->decimal('purchase_cost', 16, 2)->default(0); // tổng tiền con giống ban đầu
            $table->decimal('initial_weight_kg', 10, 3)->nullable(); // TB mỗi con lúc nhập
            $table->string('source', 120)->nullable();
            $table->string('status', 12)->default('active');     // active | closed
            $table->date('closed_on')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['farm_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
