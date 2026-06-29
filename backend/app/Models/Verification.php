<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Verification extends Model
{
    use HasFactory;

<<<<<<< HEAD
    public $timestamps = false; // ← ajouter cette ligne
=======
    public $timestamps = false;
>>>>>>> bar

    protected $fillable = [
        'document_id',
        'ip_address',
        'ville',
        'pays',
        'statut_au_scan',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
}
