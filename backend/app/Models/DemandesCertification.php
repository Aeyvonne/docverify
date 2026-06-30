<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DemandesCertification extends Model
{
    use HasFactory;

    protected $table = 'demandes_certification';

    protected $fillable = [
        'user_id',
        'ninea',
        'rccm',
        'fichier_preuve',
        'statut',
        'motif_refus',
        'traite_par',
        'traite_le',
    ];

    protected $casts = [
        'traite_le' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par');
    }

    public function adminNotifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'demande_id');
    }
}
