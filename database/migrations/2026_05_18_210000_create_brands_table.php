<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->smallInteger('status')->default(1)->comment('1: Active, 0: Inactive');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        collect(['models', 'truck_appliances'])
            ->filter(fn (string $table) => Schema::hasTable($table) && Schema::hasColumn($table, 'brand'))
            ->flatMap(fn (string $table) => DB::table($table)->whereNotNull('brand')->where('brand', '!=', '')->pluck('brand'))
            ->map(fn (string $brand) => trim($brand))
            ->filter()
            ->unique()
            ->each(fn (string $brand) => DB::table('brands')->insertOrIgnore([
                'name' => $brand,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
