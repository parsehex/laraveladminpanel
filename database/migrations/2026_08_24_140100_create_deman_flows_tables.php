<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('deman_flows')) {
            Schema::create('deman_flows', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 64)->unique();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('deman_flow_prompts')) {
            Schema::create('deman_flow_prompts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('deman_flow_id')->constrained('deman_flows')->cascadeOnDelete();
                $table->string('key', 64);
                $table->string('description');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['deman_flow_id', 'key']);
                $table->index(['deman_flow_id', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('deman_flow_prompts');
        Schema::dropIfExists('deman_flows');
    }
};
