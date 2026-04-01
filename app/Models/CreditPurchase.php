<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Models\User;

#[Fillable([
    'user_id',
    'amount',
    'total_paid',
    'stripe_session_id',
])]
class CreditPurchase extends Model
{
    public function user() {
        return $this->belongsTo(User::class);
    }
}
