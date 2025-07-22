<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    public $guarded = [];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function aggregator()
    {
        return $this->belongsTo(Aggregator::class);
    }
}
