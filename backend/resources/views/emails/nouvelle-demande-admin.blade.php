@component('mail::message')

# Nouvelle demande de certification

Bonjour,

Une nouvelle demande de certification a été soumise sur **DocVerify** et attend votre traitement.

---

## Informations de l'émetteur

| Champ | Valeur |
|---|---|
| **Nom complet** | {{ $user->prenom }} {{ $user->nom }} |
| **Email** | {{ $user->email }} |
| **Téléphone** | {{ $user->telephone ?? 'Non renseigné' }} |
| **Adresse** | {{ $user->adresse ?? 'Non renseignée' }} |

---

## Informations de l'institution

| Champ | Valeur |
|---|---|
| **Nom de l'institution** | {{ $user->nom_institution ?? 'N/A' }} |
| **Type d'institution** | {{ $user->type_institution ?? 'N/A' }} |
| **NINEA** | {{ $demande->ninea }} |
| **RCCM** | {{ $demande->rccm }} |

---

## Détails de la demande

| Champ | Valeur |
|---|---|
| **Référence** | #{{ $demande->id }} |
| **Soumise le** | {{ $demande->created_at->format('d/m/Y à H:i') }} |
| **Statut** | En attente de traitement |

@if (!empty($demande->message))

**Message de l'émetteur :**

> {{ $demande->message }}

@endif

---

> 📎 Le fichier justificatif (RCCM / extrait de registre) est joint à cet email.

@component('mail::button', ['url' => $adminUrl, 'color' => 'primary'])
Traiter la demande →
@endcomponent

Merci,<br>
**L'équipe {{ config('app.name') }}**

@endcomponent
