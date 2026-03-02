<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| Alarm — Installation Channel
|--------------------------------------------------------------------------
|
| Canal privé par installation pour les événements alarme temps réel.
| L'utilisateur doit être rattaché à l'installation (via son client).
|
*/

Broadcast::channel('installation.{uuid}', function ($user, string $uuid) {
    // Administrateurs et managers ont accès à tout
    if ($user->hasAnyRole([\App\Enums\UserRole::ADMINISTRATOR, \App\Enums\UserRole::MANAGER])) {
        return true;
    }

    // Clients : vérifier que l'installation appartient à leur contrat
    $client = $user->client;

    if (!$client) {
        return false;
    }

    return \App\Models\Installation::where('uuid', $uuid)
        ->where('client_uuid', $client->uuid)
        ->exists();
});

