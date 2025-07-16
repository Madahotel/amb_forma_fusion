<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
   Schema::table('users', function (Blueprint $table) {
    if (!Schema::hasColumn('users', 'code_affiliation')) {
        $table->string('code_affiliation')->nullable();
    }
    if (!Schema::hasColumn('users', 'pays')) {
        $table->string('pays')->nullable();
    }
});
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
