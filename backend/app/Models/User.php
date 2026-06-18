<?php

namespace App\Models;


use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; 
use Illuminate\Notifications\Notifiable;

use App\Models\Document;
use App\Models\DemandesCertification;



class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Documents émis par cet utilisateur.
     */
    public function documents()
    {
        return $this->hasMany(Document::class, 'emetteur_id');
    }

    
    public function revokedDocuments()
    {
        return $this->hasMany(Document::class, 'revoked_by');
    }

    

    public function demandesCertifications()
    {
        return $this->hasMany(DemandesCertification::class, 'user_id');
    }

    
    public function traiteDemandesCertifications()
    {
        return $this->hasMany(DemandesCertification::class, 'traite_par');
    }

    
    public function notifications()
    {
        return $this->hasMany('App\\Models\\Notification', 'admin_id');
    }


    protected $fillable = [
        'name',     // Nom de l'utilisateur
        'email',    // Adresse email (utilisée pour la connexion)
        'password', // Mot de passe (sera automatiquement hashé via $casts)
    ];

    /**
     * Champs masqués lors de la sérialisation (ex: retour JSON d'une API).
     * Ces champs ne seront jamais exposés dans les réponses.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',       // Jamais exposé pour des raisons de sécurité
        'remember_token', // Token de session "se souvenir de moi"
    ];

    /**
     * Conversion automatique des types pour certains champs.
     * Appelé automatiquement par Laravel à l'accès aux attributs.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime', // Converti en objet Carbon automatiquement
            'password'          => 'hashed',   // Hashé automatiquement avant stockage en BDD
        ];
    }
}