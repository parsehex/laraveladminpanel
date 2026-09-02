<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_import_runs', function (Blueprint $table) {
            $table->id();
            $table->string('dump_path');
            $table->string('dump_sha256', 64);
            $table->string('mode', 32);
            $table->boolean('dry_run')->default(false);
            $table->string('report_path')->nullable();
            $table->json('patch_hashes')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();
        });

        Schema::create('legacy_id_map', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_table', 64);
            $table->unsignedBigInteger('legacy_id');
            $table->unsignedBigInteger('new_id');
            $table->foreignId('legacy_import_run_id')->nullable()->constrained('legacy_import_runs')->nullOnDelete();
            $table->timestamps();

            $table->unique(['legacy_table', 'legacy_id']);
            $table->index(['legacy_table', 'new_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_id_map');
        Schema::dropIfExists('legacy_import_runs');
    }
};
