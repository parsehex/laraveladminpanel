<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('truck_appliances', function (Blueprint $table) {
            if (! Schema::hasColumn('truck_appliances', 'photos')) {
                $table->json('photos')->nullable()->after('sold_at');
            }
        });

        if (! Schema::hasTable('custom_sales')) {
            Schema::create('custom_sales', function (Blueprint $table) {
                $table->id();
                $table->string('model_number');
                $table->string('serial_number');
                $table->decimal('sold_price', 10, 2)->default(0);
                $table->decimal('estimated_price', 10, 2)->default(0);
                $table->string('sold_by')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('serial_number');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_sales');

        Schema::table('truck_appliances', function (Blueprint $table) {
            if (Schema::hasColumn('truck_appliances', 'photos')) {
                $table->dropColumn('photos');
            }
        });
    }
};
