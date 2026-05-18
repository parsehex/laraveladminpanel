<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appliance_parts', function (Blueprint $table) {
            if (! Schema::hasColumn('appliance_parts', 'part_id')) {
                $table->foreignId('part_id')
                    ->nullable()
                    ->after('truck_appliance_id')
                    ->constrained('parts')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('appliance_parts', function (Blueprint $table) {
            if (Schema::hasColumn('appliance_parts', 'part_id')) {
                $table->dropConstrainedForeignId('part_id');
            }
        });
    }
};
