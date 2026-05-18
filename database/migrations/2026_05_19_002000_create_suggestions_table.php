<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('suggestions')) {
            return;
        }

        Schema::create('suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('username')->nullable();
            $table->text('suggestion');
            $table->string('urgency', 20)->default('normal');
            $table->string('status', 20)->default('pending');
            $table->json('responses')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'urgency']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suggestions');
    }
};
