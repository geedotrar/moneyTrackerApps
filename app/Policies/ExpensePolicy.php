<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasPermission('admin-view-expenses')) {
            return true;
        } 

        if ($user->hasPermission('user-view-expenses')) {
            return true;
        }

        return false;
    }

    public function view(User $authUser, Expense $expense): bool
    {
        if ($authUser->hasPermission('admin-view-expenses')) {
            return true;
        }

        if ($authUser->hasPermission('user-view-expenses') && $authUser->id === $expense->user_id) {
            return true;
        }

        return false;
    }
}
