<?php

namespace App\Policies;

use App\Models\Ingest;
use App\Models\User;

class IngestPolicy
{
    public function viewAny(?User $user): bool
    {
        return (bool)$user;
    }

    public function view(?User $user, Ingest $ingest): bool
    {
        return (bool)$user;
    }

    public function update(User $user, Ingest $ingest): bool
    {
        return method_exists($user, 'hasRole')
            ? $user->hasRole(['super-admin', 'engineer'])
            : true;
    }
}
