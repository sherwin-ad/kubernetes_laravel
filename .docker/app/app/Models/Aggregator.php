<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aggregator extends Model
{
    use HasFactory, SoftDeletes;

    public $guarded = [];

    public function merchants()
    {
        return $this->hasMany(Merchant::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id','aggregator_id');
    }
}
