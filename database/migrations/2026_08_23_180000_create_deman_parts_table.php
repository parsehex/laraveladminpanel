<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deman_parts')) {
            return;
        }

        Schema::create('deman_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('truck_appliance_id')->constrained('truck_appliances')->cascadeOnDelete();
            $table->string('part_number');
            $table->string('description');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('condition', 20)->default('Good');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('truck_appliance_id');
            $table->index('part_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deman_parts');
    }
};
