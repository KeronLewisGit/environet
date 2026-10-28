<?php

namespace App\Policies;

use App\Enums\Area;
use App\Models\Enquiry;
use App\Models\User;

/** Staff see the areas their role works in (Role::areas()); administrators see everything. */
class EnquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->works(Area::Enquiries);
    }

    public function view(User $user, Enquiry $enquiry): bool
    {
        return $user->works(Area::Enquiries);
    }

    public function create(User $user): bool
    {
        return $user->works(Area::Enquiries);
    }

    public function update(User $user, Enquiry $enquiry): bool
    {
        return $user->works(Area::Enquiries);
    }

    public function delete(User $user, Enquiry $enquiry): bool
    {
        return $user->works(Area::Enquiries);
    }
}
