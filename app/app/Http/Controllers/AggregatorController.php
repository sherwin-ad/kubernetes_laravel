<?php

namespace App\Http\Controllers;

use App\Models\Aggregator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class AggregatorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $model = Aggregator::query()->orderBy('created_at', 'desc');

            return DataTables::eloquent($model)
                ->addColumn('yesterday_transaction_amount', function ($aggregator) {
                    // sum from transactions with peso sign for yesterday
                    $yesterday = now()->subDay();
                    $amount = $aggregator->transactions()
                        ->where('created_at', '>=', now()->subDay()->startOfDay())
                        ->where('created_at', '<', now()->subDay()->endOfDay())
                        ->sum('amount');

                    return '₱' . number_format($amount, 2);
                })
                ->addColumn('merchants', function ($aggregator) {
                    return view('aggregator._merchants', compact('aggregator'));
                })
                ->addColumn('daily_transaction_limit', function ($aggregator) {
                    // daily transaction limit with peso sign
                    return '₱' . number_format($aggregator->dtl, 2);
                })
                ->addColumn('running_day_amount', function ($aggregator) {
                    // sum from transactions with peso sign for the current day
                    $today = now()->startOfDay();
                    $amount = $aggregator->transactions()->where('created_at', '>=', $today)->sum('amount');
                    return '₱' . number_format($amount, 2);
                })
                // add rate
                ->addColumn('rate', function ($aggregator) {
                    return $aggregator->rate . '%';
                })
                // add to settle
                ->addColumn('to_settle', function ($aggregator) {
                    $rate = $aggregator->rate / 100;
                    //get yesterday's total amount
                    $amount = $aggregator->transactions()
                        ->where('created_at', '>=', now()->subDay()->startOfDay())
                        ->where('created_at', '<', now()->subDay()->endOfDay())
                        ->sum('amount');

                    $to_sub = $amount * $rate;

                    $amount -= $to_sub;
                    return '₱' . number_format($amount, 2);
                })

                ->addColumn('action', function ($aggregator) {
                    return view('aggregator._action', compact('aggregator'));
                })
                ->addColumn('bulk_checkbox', function ($item) {
                    // return view('partials._bulk_checkbox',compact('item'));
                })
                ->addIndexColumn()
                ->toJson();
        }
        return view('aggregator.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('aggregator.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'account_name' => 'required',
            'account_number' => 'required',
            'bank' => 'required',
            'rate' => 'required|numeric',
            'dtl' => 'required|numeric',
            'email' => 'required|email',
            'password' => 'required|min:6',
        ], [
            'name.required' => 'Aggregator name is required.',
            'account_name.required' => 'Contact person is required.',
            'account_number.required' => 'Account number is required.',
            'bank.required' => 'Bank is required.',
            'rate.required' => 'Rate is required.',
            'rate.numeric' => 'Rate must be a number.',
            'dtl.required' => 'Daily transaction limit is required.',
            'dtl.numeric' => 'Daily transaction limit must be a number.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid email address.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 6 characters.',
        ]);

        $aggregator = Aggregator::create($request->except(['email', 'password']));
        $aggregatorId = $aggregator->id;

        // create record in user table
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->aggregator_id = $aggregatorId;
        $user->save();

        return redirect()->route('aggregator.index')
            ->with('success', 'Aggregator created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Aggregator $aggregator)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Aggregator $aggregator)
    {
        return view('aggregator.edit', compact('aggregator'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Aggregator $aggregator)
    {
        $request->validate([
            'name' => 'required',
            'account_name' => 'required',
            'account_number' => 'required',
            'bank' => 'required',
            'rate' => 'required|numeric',
            'dtl' => 'required|numeric',
            'email' => 'required|email',
            //minimum password length is 6 if password is not empty
            'password' => ($request->filled('password') ? '|min:6' : ''),
        ], [
            'name.required' => 'Aggregator name is required.',
            'account_name.required' => 'Contact person is required.',
            'account_number.required' => 'Account number is required.',
            'bank.required' => 'Bank is required.',
            'rate.required' => 'Rate is required.',
            'rate.numeric' => 'Rate must be a number.',
            'dtl.required' => 'Daily transaction limit is required.',
            'dtl.numeric' => 'Daily transaction limit must be a number.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid email address.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 6 characters.',
        ]);

        $aggregator->update($request->except(['email', 'password']));

        // if no aggregator user, create one
        if (!$aggregator->user) {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;

            if ($request->filled('password')) {
                $user->password = bcrypt($request->password);
            }

            $user->aggregator_id = $aggregator->id;
            $user->save();
        } else {
            $aggregator->user->update([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->filled('password') ? bcrypt($request->password) : $aggregator->user->password,
                'aggregator_id' => $aggregator->id,
            ]);
        }

        return redirect()->route('aggregator.index')
            ->with('success', 'Aggregator updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Aggregator $aggregator)
    {
        if ($aggregator->delete()) {
            return redirect()->route('aggregator.index')
                ->with('success', 'Aggregator deleted successfully.');
        } else {
            return redirect()->route('aggregator.index')
                ->with('error', 'Aggregator could not be deleted.');
        }
    }

    public function updateCallback(Request $request)
    {
        // update aggregator callback url
        $aggregator = Auth::user()->aggregator;
        $aggregator->callback_url = $request->callback_url;
        $aggregator->save();

        return redirect()->route('dashboard')
            ->with('success', 'Callback URL updated successfully.');
    }
}
