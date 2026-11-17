<?php

namespace TableKitDemo\Policies;

use TableKitDemo\Models\Client;
use TableKitDemo\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return (int) $user->id === 1;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function view(User $user, Client $client): bool
    {
        return $this->viewAny($user) && $client->tenant_id === 1;
    }

    public function update(User $user, Client $client): bool
    {
        return $this->view($user, $client);
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->view($user, $client);
    }
}
