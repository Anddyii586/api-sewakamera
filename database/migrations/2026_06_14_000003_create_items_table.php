<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->text('description')->nullable();
            $table->decimal('daily_price', 12, 2);
            $table->integer('stock')->default(1);
            $table->enum('status', ['available', 'unavailable', 'maintenance'])->default('available');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
