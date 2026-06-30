@component('mail::message')
# Demande de certification approuvée ✓

Bonjour {{ $user->prenom }},

Bonne nouvelle ! Votre demande de certification pour **{{ $user->nom_institution }}** a été **approuvée**.

Vous pouvez désormais émettre tous les types de documents officiels sur DocVerify.

@component('mail::button', ['url' => $dashboardUrl, 'color' => 'success'])
Accéder à mon tableau de bord
@endcomponent

Merci de votre confiance,<br>
{{ config('app.name') }}
@endcomponent
