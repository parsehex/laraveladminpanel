<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('testing_flows')) {
            Schema::create('testing_flows', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 64)->unique();
                $table->string('name');
                $table->unsignedInteger('version')->default(1);
                $table->string('start', 64);
                $table->json('steps');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('testing_flow_versions')) {
            Schema::create('testing_flow_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('testing_flow_id')->constrained('testing_flows')->cascadeOnDelete();
                $table->unsignedInteger('version');
                $table->string('name');
                $table->string('start', 64);
                $table->json('steps');
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['testing_flow_id', 'version']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('testing_flow_versions');
        Schema::dropIfExists('testing_flows');
    }
};
