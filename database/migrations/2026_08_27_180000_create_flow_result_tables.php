<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('testing_results')) {
            Schema::create('testing_results', function (Blueprint $table) {
                $table->id();
                $table->string('result_id', 64)->unique();
                $table->foreignId('truck_appliance_id')->constrained('truck_appliances')->cascadeOnDelete();
                $table->string('flow_slug', 64);
                $table->unsignedInteger('flow_version')->default(1);
                $table->string('resulting_status');
                $table->json('answers');
                $table->text('notes')->nullable();
                $table->json('flow_snapshot');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('user_name')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['truck_appliance_id', 'completed_at']);
            });
        }

        if (! Schema::hasTable('repair_results')) {
            Schema::create('repair_results', function (Blueprint $table) {
                $table->id();
                $table->string('result_id', 64)->unique();
                $table->foreignId('truck_appliance_id')->constrained('truck_appliances')->cascadeOnDelete();
                $table->string('type', 32)->default('reevaluation');
                $table->string('source_testing_result_id', 64)->nullable();
                $table->string('source_flow_slug', 64)->nullable();
                $table->unsignedInteger('source_flow_version')->nullable();
                $table->string('resulting_status');
                $table->json('answers');
                $table->json('failed_steps_snapshot')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('user_name')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['truck_appliance_id', 'completed_at']);
            });
        }

        if (! Schema::hasTable('repair_diagnoses')) {
            Schema::create('repair_diagnoses', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('truck_appliance_id')->constrained('truck_appliances')->cascadeOnDelete();
                $table->text('diagnosis');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('user_name')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['truck_appliance_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_diagnoses');
        Schema::dropIfExists('repair_results');
        Schema::dropIfExists('testing_results');
    }
};
