@component('mail::message')
# Demande de certification refusée

Bonjour {{ $user->prenom }},

Votre demande de certification pour **{{ $user->nom_institution }}** n'a pas pu être approuvée.

**Motif du refus :**
> {{ $motif }}

Vous pouvez soumettre une nouvelle demande en corrigeant les éléments mentionnés ci-dessus.

@component('mail::button', ['url' => $retryUrl, 'color' => 'primary'])
Soumettre une nouvelle demande
@endcomponent

Cordialement,<br>
{{ config('app.name') }}
@endcomponent
