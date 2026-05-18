<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appliance_parts', function (Blueprint $table) {
            if (Schema::hasColumn('appliance_parts', 'part_number')) {
                $table->dropUnique(['part_number']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('appliance_parts', function (Blueprint $table) {
            if (Schema::hasColumn('appliance_parts', 'part_number')) {
                $table->unique('part_number');
            }
        });
    }
};
