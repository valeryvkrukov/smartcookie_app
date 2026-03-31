<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Client extends User
{
    protected $table = 'users';

    protected static function booted(): void
    {
        static::addGlobalScope('role', fn (Builder $builder) => $builder->where('role', 'customer'));
    }

    /**
     * Check for ability to buy credits
     */
    public function canPurchaseCredits(): bool
    {
        return $this->credit && $this->credit->dollar_cost_per_credit !== null;
    }
}