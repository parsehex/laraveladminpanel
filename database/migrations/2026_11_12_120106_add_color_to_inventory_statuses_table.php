<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Canonical backgrounds keyed by status name (display case).
     *
     * @var array<string, string>
     */
    private const COLORS = [
        'Triage' => '#ffffff',
        'Testing' => '#67d4f1',
        'Repair' => '#f97316',
        'Breakdown' => '#dc2626',
        'Demanufacture' => '#dc2626',
        'Cleaning' => '#7c2d12',
        'Ready' => '#3b82f6',
        'Scrap' => '#111827',
        'Show Room' => '#9333ea',
        'Sent To Ebay' => '#6366f1',
        'Video' => '#c026d3',
        'Quality Control QC' => '#16a34a',
        'Sold' => '#059669',
        'Holding for parts' => '#eab308',
        'Holding' => '#ec4899',
    ];

    public function up(): void
    {
        Schema::table('inventory_statuses', function (Blueprint $table) {
            $table->string('color', 7)->default('#6b7280')->after('auto_location');
        });

        foreach (self::COLORS as $name => $color) {
            DB::table('inventory_statuses')
                ->where('name', $name)
                ->update(['color' => $color]);
        }
    }

    public function down(): void
    {
        Schema::table('inventory_statuses', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
