<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'credit_balance', 'dollar_cost_per_credit'])]
class Credit extends Model
{
    // Relation to Client
    public function client()
    {
        return $this->belongsTo(Client::class, 'user_id');
    }
}
