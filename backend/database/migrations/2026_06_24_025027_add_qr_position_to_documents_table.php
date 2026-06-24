<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Position du QR code en millimètres depuis le coin haut-gauche de la page.
            // null = position automatique (coin bas-droit, comportement par défaut).
            $table->float('qr_position_x')->nullable()->after('date_expiration');
            $table->float('qr_position_y')->nullable()->after('qr_position_x');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['qr_position_x', 'qr_position_y']);
        });
    }
};
