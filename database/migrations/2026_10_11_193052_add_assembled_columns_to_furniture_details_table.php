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
        Schema::table('furniture_details', function (Blueprint $table) {
            $table->timestamp('assembled_at')->nullable()->after('truck_appliance_id');
            $table->foreignId('assembled_by')->nullable()->after('assembled_at')->constrained('users')->nullOnDelete();
            $table->string('assembled_by_name')->nullable()->after('assembled_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('furniture_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assembled_by');
            $table->dropColumn(['assembled_at', 'assembled_by_name']);
        });
    }
};
