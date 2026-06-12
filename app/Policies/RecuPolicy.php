<?php

namespace App\Policies;

use App\Models\Recu;
use App\Models\User;

class RecuPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Recu $recu): bool
    {
        return $user->id === $recu->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Recu $recu): bool
    {
        return $user->id === $recu->user_id;
    }

    public function delete(User $user, Recu $recu): bool
    {
        return $user->id === $recu->user_id;
    }

    public function restore(User $user, Recu $recu): bool
    {
        return false;
    }

    public function forceDelete(User $user, Recu $recu): bool
    {
        return false;
    }
}