<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('truck_appliances', function (Blueprint $table) {
            if (! Schema::hasColumn('truck_appliances', 'unit_label')) {
                $table->string('unit_label')->nullable();
            }
            if (! Schema::hasColumn('truck_appliances', 'quantity')) {
                $table->integer('quantity')->default(1);
            }
            if (! Schema::hasColumn('truck_appliances', 'price')) {
                $table->decimal('price', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('truck_appliances', 'fuel_type')) {
                $table->string('fuel_type')->nullable();
            }
        });

        Schema::table('models', function (Blueprint $table) {
            if (! Schema::hasColumn('models', 'variations')) {
                $table->json('variations')->nullable();
            }
        });

        Schema::table('parts', function (Blueprint $table) {
            if (! Schema::hasColumn('parts', 'diagram_name')) {
                $table->string('diagram_name')->nullable();
            }
            if (! Schema::hasColumn('parts', 'image_url')) {
                $table->text('image_url')->nullable();
            }
            if (! Schema::hasColumn('parts', 'make')) {
                $table->string('make')->nullable();
            }
            if (! Schema::hasColumn('parts', 'item')) {
                $table->string('item')->nullable();
            }
        });

        if (! Schema::hasTable('model_parts')) {
            Schema::create('model_parts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('model_id')->constrained('models')->cascadeOnDelete();
                $table->foreignId('part_id')->constrained('parts')->cascadeOnDelete();
                $table->string('variation')->nullable();
                $table->timestamps();

                $table->unique(['model_id', 'part_id', 'variation']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('model_parts');

        Schema::table('parts', function (Blueprint $table) {
            foreach (['item', 'make', 'image_url', 'diagram_name'] as $column) {
                if (Schema::hasColumn('parts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('models', function (Blueprint $table) {
            if (Schema::hasColumn('models', 'variations')) {
                $table->dropColumn('variations');
            }
        });

        Schema::table('truck_appliances', function (Blueprint $table) {
            foreach (['fuel_type', 'price', 'quantity', 'unit_label'] as $column) {
                if (Schema::hasColumn('truck_appliances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
