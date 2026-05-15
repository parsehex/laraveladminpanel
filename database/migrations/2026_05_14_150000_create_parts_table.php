<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->string('part_number')->unique();
            $table->string('product_name')->nullable();
            $table->string('model_compatibility')->nullable();
            $table->unsignedInteger('total_stock')->default(0);
            $table->decimal('retail_price', 12, 2)->default(0);
            $table->decimal('your_price', 12, 2)->default(0);
            $table->string('cross_reference')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_name');
            $table->index('model_compatibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
