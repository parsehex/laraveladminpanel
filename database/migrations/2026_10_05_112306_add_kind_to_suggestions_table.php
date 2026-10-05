<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suggestions', function (Blueprint $table) {
            $table->string('kind', 32)->default('suggestion')->after('id');
            $table->index(['kind', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('suggestions', function (Blueprint $table) {
            $table->dropIndex(['kind', 'status']);
            $table->dropColumn('kind');
        });
    }
};
