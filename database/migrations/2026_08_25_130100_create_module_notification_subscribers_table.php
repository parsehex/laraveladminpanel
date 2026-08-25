<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('module_notification_subscribers')) {
            return;
        }

        Schema::create('module_notification_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('module', 64);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['module', 'user_id']);
            $table->index('module');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_notification_subscribers');
    }
};
