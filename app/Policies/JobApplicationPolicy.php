<?php

namespace App\Policies;

use App\Enums\Area;
use App\Models\JobApplication;
use App\Models\User;

/** Staff see the areas their role works in (Role::areas()); administrators see everything. */
class JobApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->works(Area::Careers);
    }

    public function view(User $user, JobApplication $application): bool
    {
        return $user->works(Area::Careers);
    }

    public function create(User $user): bool
    {
        return $user->works(Area::Careers);
    }

    public function update(User $user, JobApplication $application): bool
    {
        return $user->works(Area::Careers);
    }

    public function delete(User $user, JobApplication $application): bool
    {
        return $user->works(Area::Careers);
    }

    /** CVs carry personal data: HR and administrators only. */
    public function openCv(User $user, JobApplication $application): bool
    {
        return $application->cv_path !== null && $this->view($user, $application);
    }
}
