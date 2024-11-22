<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Create a new policy instance.
     */
     public function viewAny(User $user): bool
     {
         if ($user->hasPermission('admin-view-users')) {
             return true;
         } 
 
         return false;
     }

    public function view(User $authUser, User $user): bool
    {
        if ($authUser->hasPermission('admin-view-users')) {
            return true;
        }

        if ($authUser->hasPermission('user-view-users') && $authUser->id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->hasPermission('create-view-users')) {
            return true;
        } 

        return false;
    }
    public function update(User $authUser, User $user): bool
    {
        if ($authUser->hasPermission('admin-view-users')) {
            return true;
        }

        if ($authUser->hasPermission('user-view-users') && $authUser->id === $user->id) {
            return true;
        }

        return false;
    }

    public function deleteAny(User $authUser): bool
    {
        if ($authUser->hasPermission('admin-delete-users')) {
            return true;
        }

        return false;
    }
}
