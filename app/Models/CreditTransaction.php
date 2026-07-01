<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('credit_transactions')]
#[Fillable(['user_id', 'amount', 'price_pkr', 'payment_method', 'payment_phone', 'payment_proof', 'status'])]
class CreditTransaction extends Model
{
    /**
     * Get the user that owns the credit transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
