<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'platform')) {
                $table->string('platform', 32)->nullable();
            }
        });

        if (! Schema::hasTable('kits')) {
            Schema::create('kits', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('sop')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('kit_inventory')) {
            Schema::create('kit_inventory', function (Blueprint $table) {
                $table->id();
                $table->string('part_name')->unique();
                $table->integer('current_stock')->default(0);
                $table->integer('min_level')->default(0);
                $table->integer('amazon_stock')->default(0);
                $table->integer('amazon_min_level')->default(0);
                $table->integer('shopify_stock')->default(0);
                $table->integer('shopify_min_level')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('kit_parts')) {
            Schema::create('kit_parts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kit_id')->constrained('kits')->cascadeOnDelete();
                $table->string('part_name');
                $table->integer('quantity_per_kit')->default(1);
                $table->timestamps();

                $table->unique(['kit_id', 'part_name']);
            });
        }

        if (! Schema::hasTable('kit_assignments')) {
            Schema::create('kit_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kit_id')->constrained('kits')->cascadeOnDelete();
                $table->integer('quantity');
                $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
                $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
                $table->date('due_date')->nullable();
                $table->text('notes')->nullable();
                $table->string('platform', 32);
                $table->string('status', 32)->default('pending');
                $table->boolean('raw_stock_deducted')->default(false);
                $table->timestamps();

                $table->index(['status', 'platform']);
                $table->index('assigned_to');
            });
        }

        if (! Schema::hasTable('kit_messages')) {
            Schema::create('kit_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('assignment_id')->constrained('kit_assignments')->cascadeOnDelete();
                $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
                $table->text('message');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kit_messages');
        Schema::dropIfExists('kit_assignments');
        Schema::dropIfExists('kit_parts');
        Schema::dropIfExists('kit_inventory');
        Schema::dropIfExists('kits');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'platform')) {
                $table->dropColumn('platform');
            }
        });
    }
};
