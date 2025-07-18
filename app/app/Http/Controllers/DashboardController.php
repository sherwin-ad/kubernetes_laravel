<?php

namespace App\Http\Controllers;

use App\Models\Aggregator;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->is_admin) {
            return $this->adminDashboard($request);
        } else {
            return $this->aggregatorDashboard($request);
        }
    }

    public function adminDashboard(Request $request)
    {
        $data['aggregators'] = Aggregator::all();

        // show last seven days in words like '22th', '23th', '24th', '25th', '26th', '27th', '28th'
        $data['days'] = collect(range(0, 6))->map(function ($day) {
            return now()->subDays($day)->format('jS');
        })->reverse();

        // remove keys
        $data['days'] = $data['days']->values();

        // per day transaction amount last seven days
        $data['per_day_transaction_amount_high'] = collect(range(0, 6))->map(function ($day) {
            return Aggregator::with('transactions')->get()->map(function ($aggregator) use ($day) {
                return $aggregator->transactions->where('created_at', '>=', now()->subDays($day)->startOfDay())
                    ->where('created_at', '<=', now()->subDays($day)->endOfDay())
                    ->sum('amount');
            })->sum();
        })->reverse();

        // remove keys
        $data['per_day_transaction_amount_high'] = $data['per_day_transaction_amount_high']->values();


        $data['per_day_transaction_amount_low'] = collect(range(0, 6))->map(function ($day) {
            return Aggregator::with('transactions')->get()->map(function ($aggregator) use ($day) {
                return $aggregator->transactions->where('created_at', '>=', now()->subDays($day)->startOfDay())
                    ->where('created_at', '<=', now()->subDays($day)->endOfDay())
                    ->sum('amount');
            })->sum();
        })->reverse();

        // remove keys
        $data['per_day_transaction_amount_low'] = $data['per_day_transaction_amount_low']->values();


        return view('dashboard.admin', $data);
    }

    public function aggregatorDashboard(Request $request)
    {
        $aggregator = Auth::user()->aggregator;

        if ($request->ajax()) {
            $model = Transaction::query()->with(['merchant'])->where('aggregator_id', $aggregator->id);
            $totals_query = $aggregator->transactions();

            if ($request->start_date) {
                $model = $model->whereDate('created_at', '>=', $request->start_date);
                $totals_query = $totals_query->whereDate('created_at', '>=', $request->start_date);
            }

            if ($request->end_date) {
                $model = $model->whereDate('created_at', '<=', $request->end_date);
                $totals_query = $totals_query->whereDate('created_at', '<=', $request->end_date);
            }

            $model = $model->orderBy('created_at', 'desc');

            $transactions = $totals_query->get();

            $average_amount = number_format($transactions->avg('amount'), 2);
            $total_amount = number_format($transactions->sum('amount'), 2);
            $total_count = $transactions->count();

            return DataTables::eloquent($model)
                ->addColumn('average_amount', function () use ($average_amount) {
                    return $average_amount;
                })
                ->addColumn('total_amount', function () use ($total_amount) {
                    return $total_amount;
                })
                ->addColumn('total_count', function () use ($total_count) {
                    return $total_count;
                })
                ->addColumn('merchant', function ($transaction) {
                    return $transaction->merchant->name;
                })

                ->addColumn('amount', function ($transaction) {
                    return '₱' . number_format($transaction->amount, 2);
                })

                ->addColumn('status', function ($transaction) {
                    return view('transaction._status', ['status' => $transaction->payment_status]);
                })
                ->addColumn('created_at', function ($transaction) {
                    return $transaction->created_at->format('M d, Y h:i A');
                })
                ->addColumn('bulk_checkbox', function ($item) {})
                ->addIndexColumn()
                ->toJson();
        }
        $transactions_today = $aggregator->transactions()->where('created_at', '>=', now()->startOfDay());
        $transactions_yesterday = $aggregator->transactions()
            ->where('created_at', '>=', now()->subDay()->startOfDay())
            ->where('created_at', '<', now()->subDay()->endOfDay());

        $data['running_day_amount'] = '₱' . number_format($transactions_today->sum('amount'), 2);
        $data['yesterday_amount'] = '₱' . number_format($transactions_yesterday->where('payment_status', 'paid')->sum('amount'), 2);

        return view('dashboard.aggregator', $data);
    }
}
