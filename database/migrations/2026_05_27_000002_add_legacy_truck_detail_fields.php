<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('truck_appliances', function (Blueprint $table) {
            if (! Schema::hasColumn('truck_appliances', 'subcategory')) {
                $table->string('subcategory')->nullable();
            }
            if (! Schema::hasColumn('truck_appliances', 'original_order_number')) {
                $table->string('original_order_number')->nullable();
            }
            if (! Schema::hasColumn('truck_appliances', 'return_reason')) {
                $table->string('return_reason')->nullable();
            }
            if (! Schema::hasColumn('truck_appliances', 'return_problems')) {
                $table->text('return_problems')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('truck_appliances', function (Blueprint $table) {
            foreach (['return_problems', 'return_reason', 'original_order_number', 'subcategory'] as $column) {
                if (Schema::hasColumn('truck_appliances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
