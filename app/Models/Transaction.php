<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'user_payer_id',
        'amount',
        'type',
        'status',
        'beneficiary_id',
        'payer_name'
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_payer_id');
    }

    protected static function booted()
    {
        static::addGlobalScope('withBeneficiary', function (Builder $builder) {
            $builder->with(['payer', 'beneficiary']);
        });
    }
}
