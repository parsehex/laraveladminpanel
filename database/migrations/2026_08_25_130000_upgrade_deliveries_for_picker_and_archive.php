<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            if (! Schema::hasColumn('deliveries', 'notes')) {
                $table->text('notes')->nullable()->after('order_appliances');
            }

            if (! Schema::hasColumn('deliveries', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('created_by');
                $table->index('completed_at');
            }
        });

        if (! Schema::hasTable('delivery_truck_appliance')) {
            Schema::create('delivery_truck_appliance', function (Blueprint $table) {
                $table->id();
                $table->foreignId('delivery_id')->constrained('deliveries')->cascadeOnDelete();
                $table->foreignId('truck_appliance_id')->constrained('truck_appliances')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['delivery_id', 'truck_appliance_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_truck_appliance');

        Schema::table('deliveries', function (Blueprint $table) {
            if (Schema::hasColumn('deliveries', 'completed_at')) {
                $table->dropIndex(['completed_at']);
                $table->dropColumn('completed_at');
            }

            if (Schema::hasColumn('deliveries', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
