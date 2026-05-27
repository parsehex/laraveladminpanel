<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            if (! Schema::hasColumn('trucks', 'shipping_cost')) {
                $table->decimal('shipping_cost', 12, 2)->default(0)->after('cost_of_truck');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            if (Schema::hasColumn('trucks', 'shipping_cost')) {
                $table->dropColumn('shipping_cost');
            }
        });
    }
};
