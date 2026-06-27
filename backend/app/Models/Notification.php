<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\User;
use App\Models\Verification;
use App\Models\DemandesCertification;


/**
 * Modèle représentant un document certifié numériquement.
 * Gère l'émission, la sécurisation cryptographique et la révocation des documents.
 */
class Notification extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'admin_id',
        'demande_id',
        'message',
        'lu',
        'created_at',
    ];

    protected $casts = [
        'lu' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function demande(): BelongsTo
    {
        return $this->belongsTo(DemandesCertification::class, 'demande_id');
    }
}