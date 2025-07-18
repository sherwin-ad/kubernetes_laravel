<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class CronContoller extends Controller
{
    public function changeStatus()
    {
        // $transactions = Transaction::where('payment_status', 'pending')
        // ->where('created_at', '<', now()->subHours(5))
        // ->get();
        // dd($transactions);

        Transaction::where('payment_status', 'pending')
            ->where('created_at', '<', now()->subHours(5))
            ->update(['payment_status' => 'failed']);
    }
}
