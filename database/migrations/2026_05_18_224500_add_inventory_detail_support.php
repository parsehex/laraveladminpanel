<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('truck_appliances', function (Blueprint $table) {
            if (! Schema::hasColumn('truck_appliances', 'location')) {
                $table->string('location')->nullable();
            }

            if (! Schema::hasColumn('truck_appliances', 'sold_price')) {
                $table->decimal('sold_price', 10, 2)->nullable();
            }

            if (! Schema::hasColumn('truck_appliances', 'sold_by')) {
                $table->string('sold_by')->nullable();
            }

            if (! Schema::hasColumn('truck_appliances', 'sold_at')) {
                $table->timestamp('sold_at')->nullable();
            }
        });

        if (! Schema::hasTable('inventory_status_histories')) {
            Schema::create('inventory_status_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('truck_appliance_id')->constrained('truck_appliances')->cascadeOnDelete();
                $table->string('status');
                $table->text('notes')->nullable();
                $table->boolean('parts_ordered')->default(false);
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['truck_appliance_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('appliance_parts')) {
            Schema::create('appliance_parts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('truck_appliance_id')->constrained('truck_appliances')->cascadeOnDelete();
                $table->string('description');
                $table->decimal('cost', 10, 2)->default(0);
                $table->string('source')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('truck_appliance_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appliance_parts');
        Schema::dropIfExists('inventory_status_histories');

        Schema::table('truck_appliances', function (Blueprint $table) {
            foreach (['sold_at', 'sold_by', 'sold_price', 'location'] as $column) {
                if (Schema::hasColumn('truck_appliances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
