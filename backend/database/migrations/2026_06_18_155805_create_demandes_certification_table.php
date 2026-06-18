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
    Schema::create('demandes_certification', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->string('ninea');
        $table->string('rccm');
        $table->string('fichier_preuve');
        $table->enum('statut', ['en_attente', 'approuvee', 'refusee'])->default('en_attente');
        $table->text('motif_refus')->nullable();
        $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('traite_le')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes_certification');
    }
};
