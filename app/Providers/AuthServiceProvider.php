<?php

namespace App\Providers;

use App\Models\Balance;
use App\Models\User;
use App\Policies\BalancePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Balance::class => BalancePolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();
    }
}
