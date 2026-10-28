<?php

namespace App\Models;

use App\Enums\Area;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Role $role
 * @property bool $active
 */
#[Fillable(['name', 'email', 'password', 'role', 'active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'active' => 'boolean',
        ];
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function works(Area $area): bool
    {
        return $this->active && in_array($area, $this->role->areas(), true);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * Active staff who work in an area (for assignment pickers).
     *
     * @param  Builder<self>  $query
     */
    public function scopeWorking(Builder $query, Area $area): void
    {
        $roles = array_values(array_map(fn (Role $r) => $r->value, array_filter(Role::cases(), fn (Role $r) => in_array($area, $r->areas(), true))));
        $query->active()->whereIn('role', $roles);
    }
}
