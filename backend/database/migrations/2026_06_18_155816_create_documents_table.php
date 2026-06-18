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
    Schema::create('documents', function (Blueprint $table) {
        $table->id();
        $table->foreignId('emetteur_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
        $table->string('titre');
        $table->string('type');
        $table->string('fichier_original');
        $table->string('hash_sha256')->unique();
        $table->string('qr_token')->unique();
        $table->string('pdf_certifie');
        $table->enum('statut', ['actif', 'revoque', 'expire'])->default('actif');
        $table->text('motif_revocation')->nullable();
        $table->string('pin_hash')->nullable();
        $table->date('date_emission');
        $table->date('date_expiration')->nullable();
        $table->timestamp('revoked_at')->nullable();
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
