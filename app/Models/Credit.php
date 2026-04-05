<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'credit_balance', 'dollar_cost_per_credit', 'pending_payment_amount', 'pending_payment_method'])]
class Credit extends Model
{
    // ── Relation: belongs to the client user
    public function client()
    {
        return $this->belongsTo(Client::class, 'user_id');
    }

    protected function casts(): array
    {
        return [
            'credit_balance' => 'float',
            'dollar_cost_per_credit' => 'float',
        ];
    }
}
