<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class referral_payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payout_amount',
        'status',
    ];
}
