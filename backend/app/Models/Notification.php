<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\User;
use App\Models\Verification;

/**
 * Modèle représentant un document certifié numériquement.
 * Gère l'émission, la sécurisation cryptographique et la révocation des documents.
 */
class Document extends Model
{
    use HasFactory;

    // Champs autorisés à être remplis en masse (mass assignment)
    protected $fillable = [
        'emetteur_id',      // ID de l'utilisateur/organisation qui émet le document
        'revoked_by',       // ID de l'utilisateur qui a révoqué le document
        'titre',            // Titre/nom du document
        'type',             // Type de document : diplôme, attestation, contrat...
        'fichier_original', // Fichier brut uploadé avant certification
        'hash_sha256',      // Empreinte cryptographique SHA-256 (garantit l'intégrité)
        'qr_token',         // Token unique pour le QR code de vérification externe
        'pdf_certifie',     // Chemin vers le PDF final avec cachet de certification
        'statut',           // État : actif, révoqué, expiré
        'motif_revocation', // Raison de la révocation (rempli si statut = révoqué)
        'pin_hash',         // PIN hashé pour protéger l'accès au document
        'date_emission',    // Date d'émission officielle du document
        'date_expiration',  // Date de fin de validité du document
        'revoked_at',       // Horodatage exact de la révocation
    ];

    // Conversion automatique des types pour certains champs
    protected $casts = [
        'date_emission'  => 'date',     
        'date_expiration' => 'date',    
        'revoked_at'     => 'datetime', 
    ];

    
    public function emetteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emetteur_id');
    }

    
    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    
    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class, 'document_id');
    }
}