<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Notification;

/**
 * Modèle représentant une demande de certification soumise par un utilisateur.
 * Contient les informations fiscales/commerciales et le suivi du traitement.
 */
class DemandesCertification extends Model
{
    use HasFactory;

    // Nom explicite de la table en base de données
    protected $table = 'demandes_certification';

    // Champs autorisés à être remplis en masse (mass assignment)
    protected $fillable = [
        'user_id',        // L'utilisateur qui soumet la demande
        'ninea',          // Numéro d'identification fiscale (Sénégal)
        'rccm',           // Registre du Commerce et du Crédit Mobilier
        'fichier_preuve', // Fichier justificatif uploadé par l'utilisateur
        'statut',         // État de la demande : en_attente, approuvée, refusée
        'motif_refus',    // Raison du refus (rempli si statut = refusée)
        'traite_par',     // ID de l'admin qui a traité la demande
        'traite_le',      // Date et heure du traitement
    ];

    protected $casts = [
        'traite_le' => 'datetime', // Converti en objet Carbon automatiquement
    ];

    /**
     * Alias vers notifications() — conservé pour rétrocompatibilité.
     * Non utilisé activement dans l'application.
     */
    public function traiteNotifications()
    {
        return $this->notifications();
    }

    /**
     * Relation vers l'utilisateur qui a soumis la demande.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    
    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par');
    }

    
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'demande_id');
    }
}