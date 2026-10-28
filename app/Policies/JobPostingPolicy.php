<?php

namespace App\Policies;

use App\Enums\Area;
use App\Models\JobPosting;
use App\Models\User;

/** Staff see the areas their role works in (Role::areas()); administrators see everything. */
class JobPostingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->works(Area::Careers);
    }

    public function view(User $user, JobPosting $posting): bool
    {
        return $user->works(Area::Careers);
    }

    public function create(User $user): bool
    {
        return $user->works(Area::Careers);
    }

    public function update(User $user, JobPosting $posting): bool
    {
        return $user->works(Area::Careers);
    }

    public function delete(User $user, JobPosting $posting): bool
    {
        return $user->works(Area::Careers);
    }
}
