<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Taille du QR Code en millimètres.
            // Plage autorisée : 15mm (discret) à 60mm (très visible).
            // null = valeur par défaut du service (25mm).
            $table->unsignedSmallInteger('qr_size_mm')->nullable()->after('qr_position_y');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('qr_size_mm');
        });
    }
};
