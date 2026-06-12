<?php

namespace App\Policies;

use App\Models\Recu;
use App\Models\User;

class RecuPolicy
{
    public function view(User $user, Recu $recu): bool
    {
        return $recu->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, Recu $recu): bool
    {
        return $recu->user_id === $user->id;
    }
}
