<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Merchant extends Model
{
    use HasFactory, SoftDeletes;
    
    public $guarded=[];

    public function aggregator()
    {
        return $this->belongsTo(Aggregator::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
