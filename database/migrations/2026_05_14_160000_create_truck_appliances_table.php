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
        Schema::create('truck_appliances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('truck_id')->constrained('trucks')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('model_id')->nullable()->constrained('models')->nullOnDelete();
            $table->string('serial_number')->nullable();
            $table->string('brand')->nullable();
            $table->string('product_name')->nullable();
            $table->decimal('msrp', 10, 2)->default(0);
            $table->string('receiving_condition')->nullable();
            $table->string('status')->nullable();
            $table->decimal('total_parts_cost', 10, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('truck_id');
            $table->index('category_id');
            $table->index('model_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('truck_appliances');
    }
};
