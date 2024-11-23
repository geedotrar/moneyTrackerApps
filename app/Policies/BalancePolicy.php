<?php

namespace App\Policies;

use App\Models\Balance;
use App\Models\User;

class BalancePolicy
{
     public function viewAny(Balance $user): bool
     {
         if ($user->hasPermission('admin-view-balances')) {
             return true;
         } 
 
         return false;
     }

    public function view(User $authUser, Balance $balance): bool
    {
        if ($authUser->hasPermission('admin-view-balances')) {
            return true;
        }

        if ($authUser->hasPermission('user-view-balances') && $authUser->id === $balance->user_id) {
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
