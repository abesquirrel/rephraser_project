<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     */
    public function creating(User $user): void
    {
        // Auto-set signature from username if not provided
        if (empty($user->signature) && !empty($user->username)) {
            $user->signature = $user->username;
        }
    }

    /**
     * Handle the User "updating" event.
     */
    public function updating(User $user): void
    {
        // If username changes and signature was auto-generated, update it
        if ($user->isDirty('username') && !empty($user->username)) {
            $original = $user->getOriginal('username');
            if ($user->signature === $original) {
                $user->signature = $user->username;
            }
        }
    }
}
