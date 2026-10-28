<?php

namespace App\Policies;

use App\Enums\Area;
use App\Models\Solicitation;
use App\Models\SolicitationFile;
use App\Models\User;

/** Staff see the areas their role works in (Role::areas()); administrators see everything. */
class SolicitationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->works(Area::Solicitations);
    }

    public function view(User $user, Solicitation $solicitation): bool
    {
        return $user->works(Area::Solicitations);
    }

    public function create(User $user): bool
    {
        return $user->works(Area::Solicitations);
    }

    public function update(User $user, Solicitation $solicitation): bool
    {
        return $user->works(Area::Solicitations);
    }

    public function delete(User $user, Solicitation $solicitation): bool
    {
        return $user->works(Area::Solicitations);
    }

    /** Solicitation documents: business development and administrators, and only files of this solicitation. */
    public function openFile(User $user, Solicitation $solicitation, SolicitationFile $file): bool
    {
        return $file->solicitation_id === $solicitation->id && $this->view($user, $solicitation);
    }
}
