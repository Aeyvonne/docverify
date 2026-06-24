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
    Schema::create('verifications', function (Blueprint $table) {
        $table->id();
        $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
        $table->string('ip_address')->nullable();
        $table->string('ville')->nullable();
        $table->string('pays')->nullable();
        $table->string('statut_au_scan');
        $table->timestamp('verified_at')->useCurrent();
        // pas de timestamps() — on gère verified_at manuellement
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verifications');
    }
};
