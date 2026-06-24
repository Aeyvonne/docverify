<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;



use App\Models\User;
use App\Models\Verification;

class Document extends Model

{
    use HasFactory;

    protected $fillable = [
        'emetteur_id',
        'revoked_by',
        'titre',
        'type',
        'fichier_original',
        'hash_sha256',
        'qr_token',
        'pdf_certifie',
        'statut',
        'motif_revocation',
        'pin_hash',
        'date_emission',
        'date_expiration',
        'revoked_at',
        'qr_position_x',
        'qr_position_y',
    ];

    protected $casts = [
        'date_emission' => 'date',
        'date_expiration' => 'date',
        'revoked_at' => 'datetime',
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

