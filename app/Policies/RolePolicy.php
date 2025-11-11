<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability): bool|null
    {
        // Superadmin selalu diizinkan
        return $user->hasRole('superadmin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view_roles');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('view_roles');
    }

    public function create(User $user): bool
    {
        return $user->can('create_roles');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('update_roles');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('delete_roles');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_roles');
    }

    public function forceDelete(User $user, Role $role): bool
    {
        return $user->can('delete_roles');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('delete_roles');
    }

    public function restore(User $user, Role $role): bool
    {
        return $user->can('update_roles');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('update_roles');
    }

    public function replicate(User $user, Role $role): bool
    {
        return $user->can('create_roles');
    }

    public function reorder(User $user): bool
    {
        return $user->can('update_roles');
    }
}
