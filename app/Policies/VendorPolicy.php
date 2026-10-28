<?php

namespace App\Policies;

use App\Enums\Area;
use App\Enums\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;

/**
 * Business development and procurement both see the vendor register (BD looks for teaming
 * partners); only procurement and administrators decide on vendors, check certificates and open
 * the insurance and license documents.
 */
class VendorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->works(Area::Teaming);
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return $user->works(Area::Teaming);
    }

    public function create(User $user): bool
    {
        return $user->works(Area::Teaming);
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $this->manages($user);
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return $this->manages($user);
    }

    /** Insurance, licenses and SBA letters: procurement and administrators, only this vendor's. */
    public function openDocument(User $user, Vendor $vendor, VendorDocument $document): bool
    {
        return $document->vendor_id === $vendor->id && $this->manages($user);
    }

    private function manages(User $user): bool
    {
        return $user->isAdmin() || $user->role === Role::Procurement;
    }
}
