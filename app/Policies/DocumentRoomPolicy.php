<?php

namespace App\Policies;

use App\Enums\Area;
use App\Models\DocumentRoom;
use App\Models\User;

/** Staff see the areas their role works in (Role::areas()); administrators see everything. */
class DocumentRoomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->works(Area::Rooms);
    }

    public function view(User $user, DocumentRoom $room): bool
    {
        return $user->works(Area::Rooms);
    }

    public function create(User $user): bool
    {
        return $user->works(Area::Rooms);
    }

    public function update(User $user, DocumentRoom $room): bool
    {
        return $user->works(Area::Rooms);
    }

    public function delete(User $user, DocumentRoom $room): bool
    {
        return $user->works(Area::Rooms);
    }
}
