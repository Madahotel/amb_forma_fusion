<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'revendeur_id')) {
                $table->unsignedBigInteger('revendeur_id')->nullable()->after('id');
            }
        });
    }

    public function down(): void {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'revendeur_id')) {
                $table->dropColumn('revendeur_id');
            }
        });
    }
};

