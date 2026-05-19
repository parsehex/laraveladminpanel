<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deliveries')) {
            return;
        }

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_number');
            $table->text('customer_address');
            $table->text('order_appliances');
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->string('delivery_timeframe')->nullable();
            $table->string('delivery_type')->default('Install');
            $table->boolean('haul_away')->default(false);
            $table->boolean('collect_payment')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_name', 'customer_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
