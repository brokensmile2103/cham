<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->date('sold_on');
            $table->unsignedInteger('quantity');                  // số con xuất
            $table->decimal('weight_kg', 12, 3)->nullable();      // tổng khối lượng (nếu có cân)
            $table->string('price_unit', 6)->default('kg');       // kg | con | lo
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('total_amount', 16, 2)->default(0);
            $table->string('buyer', 120)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['farm_id', 'sold_on']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->cascadeOnDelete(); // null = chi phí chung của trại
            $table->string('category', 20)->default('khac');
            $table->decimal('amount', 16, 2);
            $table->date('occurred_on');
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['farm_id', 'occurred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('sales');
    }
};
