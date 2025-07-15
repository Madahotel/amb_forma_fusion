<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revendeur_id')->constrained('users')->onDelete('cascade');
            $table->decimal('montant', 10, 2);
            $table->enum('moyen_paiement', ['mvola', 'orange', 'banque', 'autre']);
            $table->enum('statut', ['en_attente', 'valide', 'refuse'])->default('en_attente');
            $table->timestamp('date_demande')->useCurrent();
            $table->timestamp('date_validation')->nullable();
            $table->foreignId('admin_id_validator')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
