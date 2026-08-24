<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suggestions', function (Blueprint $table) {
            if (! Schema::hasColumn('suggestions', 'page_url')) {
                $table->string('page_url', 2048)->nullable()->after('suggestion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suggestions', function (Blueprint $table) {
            if (Schema::hasColumn('suggestions', 'page_url')) {
                $table->dropColumn('page_url');
            }
        });
    }
};
