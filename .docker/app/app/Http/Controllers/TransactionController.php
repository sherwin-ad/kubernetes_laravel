<?php

namespace App\Http\Controllers;

use App\Models\Aggregator;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $model = Transaction::query()->with(['merchant', 'aggregator']);

            if ($request->aggregator) {
                $model->where('aggregator_id', $request->aggregator);
            }

            if ($request->start_date) {
                $model->where('created_at', '>=', $request->start_date);
            }

            if ($request->end_date) {
                $model->where('created_at', '<=', $request->end_date);
            }

            $model->orderBy('created_at', 'desc');

            return DataTables::eloquent($model)
                ->addColumn('merchant', fn($transaction) => $transaction->merchant->name)
                ->addColumn('aggregator', fn($transaction) => $transaction->aggregator->name)
                ->addColumn('amount', fn($transaction) => '₱' . number_format($transaction->amount, 2))
                ->addColumn('mobile_number', fn($transaction) => $transaction->mobile_number)
                ->addColumn('created_at', fn($transaction) => $transaction->created_at->format('M d, Y h:i A'))
                ->addColumn('bulk_checkbox', fn() => null)
                // yellow text for pending, green text for success, red text for failed
                ->addColumn('payment_status', function ($transaction) {
                    return view('transaction._status', ['status' => $transaction->payment_status]);
                })
                ->addIndexColumn()
                ->toJson();
        }

        return view('transaction.index', ['aggregators' => Aggregator::all()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // To be implemented
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return response()->json($request->all());
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        // To be implemented
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction)
    {
        // To be implemented
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {
        // To be implemented
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        // To be implemented
    }

    public function getTransactionsStatus(Request $request)
    {
        //check if trx_nos exists
        if (empty($request->trx_nos)) {
            return response()->json(['message' => 'trx_nos is required'], 401);
        }

        // check if trx_nos is an array
        if (!is_array($request->trx_nos)) {
            return response()->json(['message' => 'trx_nos must be an array'], 401);
        }

        // return message if does not pass validation
        if (count($request->trx_nos) > 100) {
            return response()->json(['message' => 'Maximum of 100 trx_nos per request'], 401);
        }

        // get only trx_no, created_at, and payment_status
        $transactions = Transaction::whereIn('trx_no', $request->trx_nos)
            ->get(['trx_no', 'created_at', 'payment_status']);

        return response()->json($transactions);
    }

    public function createTransaction(Request $request)
    {
        $user = $request->user();

        $aggregator = $user->aggregator;

        //aggregator callback url required
        if (!$aggregator->callback_url) {
            return response()->json(['message' => 'Aggregator callback URL is required'], 401);
        }

        // callback_url mast be a valid url sample https://domain.com
        if (!filter_var($aggregator->callback_url, FILTER_VALIDATE_URL)) {
            return response()->json(['message' => 'Callback URL is invalid. Format is https://domain.com'], 401);
        }

        // minumum amount is 20
        if ($request->amount < 20) {
            return response()->json(['message' => 'Transaction amount must be at least 20'], 401);
        }

        //amount must be numeric
        if (!is_numeric($request->amount)) {
            return response()->json(['message' => 'Transaction amount must be numeric'], 401);
        }

        // amount required
        if (!$request->amount) {
            return response()->json(['message' => 'Transaction amount is required'], 401);
        }

        // return_url required
        if (!$request->return_url) {
            return response()->json(['message' => 'Return URL is required'], 401);
        }

        // return url mast be a valid url sample https://domain.com
        if (!filter_var($request->return_url, FILTER_VALIDATE_URL)) {
            return response()->json(['message' => 'Return URL is invalid. Format is https://domain.com'], 401);
        }

        // mobile_number required
        if (!$request->mobile_number) {
            return response()->json(['message' => 'Mobile number is required'], 401);
        }

        // mobile number must be numeric
        if (!is_numeric($request->mobile_number)) {
            return response()->json(['message' => 'Mobile number must be numeric'], 401);
        }

        if (!$aggregator) {
            return response()->json(['message' => 'User is not an aggregator'], 401);
        }

        if ($aggregator->merchants->isEmpty()) {
            return response()->json(['message' => 'Aggregator has no merchants assigned'], 401);
        }

        // get sum of transactions of the day for the aggregator and check if it exceeds the daily transaction limit
        $today = now()->startOfDay();
        $transactionsSum = Transaction::where('aggregator_id', $aggregator->id)
            ->where('created_at', '>=', $today)
            ->sum('amount');
        $aggregator->transactions_sum_amount = $transactionsSum;

        if ($aggregator->transactions_sum_amount + $request->amount > $aggregator->dtl) {
            return response()->json(['message' => 'Transaction amount exceeds aggregator daily transaction limit'], 401);
        }

        // GET AGGREGRATOR MERCHANTS
        $merchants = $aggregator->merchants()->with(['transactions' => function ($query) use ($today) {
            $query->where('created_at', '>=', $today);
        }])->get();

        $merchants->each(function ($merchant) use ($today) {
            $merchant->transactions_sum_amount = $merchant->transactions
                ->where('created_at', '>=', $today)
                ->sum('amount');
        });

        $suitableMerchant = $merchants->first(function ($merchant) use ($request) {
            return $merchant->transactions_sum_amount + $request->amount <= $merchant->dtl;
        });

        if (!$suitableMerchant) {
            return response()->json(['error' => 'No merchant can process this transaction'], 400);
        }

        // return response()->json($suitableMerchant);

        $paymentIntent = $this->createPaymentIntent($suitableMerchant->api_secret, $request->amount, $request->return_url);
        $trx_no = $this->storeTransaction($suitableMerchant, $aggregator, $request, $paymentIntent);
        return response()->json([
            'trx_no' => $trx_no,
            'url' => $paymentIntent['paymentMethod'],
        ]);
    }

    public function storeTransaction($merchant, $aggregator, Request $request, $paymentIntent)
    {
        if ($merchant) {
            // count all transactions
            $transactionCount = Transaction::count() + 1;
            $trx_no = 'TRX_' . uniqid() . $transactionCount;
            Transaction::create([
                'trx_no' => $trx_no,
                'amount' => $request->amount,
                'merchant_id' => $merchant->id,
                'aggregator_id' => $aggregator->id,
                'mobile_number' => $request->mobile_number,
                'pi_id' => $paymentIntent['pi_id'],
                'payment_status' => 'pending',
                'name' => $request->name,
                'rate' => $aggregator->rate,
            ]);

            // return trx_no
            return $trx_no;
        }

        return response()->json(['message' => 'No merchant available'], 401);
    }

    public function createPaymentIntent($secret, $amount, $return_url)
    {
        $base64 = base64_encode($secret . ':');
        $amount = (int) ($amount * 100);

        $response = $this->sendCurlRequest(
            "https://api.paymongo.com/v1/payment_intents",
            "POST",
            $base64,
            [
                'data' => [
                    'attributes' => [
                        'amount' => $amount,
                        'payment_method_allowed' => ['gcash'],
                        'payment_method_options' => ['card' => ['request_three_d_secure' => 'any']],
                        'currency' => 'PHP',
                        'capture_type' => 'automatic',
                    ]
                ]
            ]
        );

        $response = json_decode($response, false);

        if (!isset($response->data->id)) {
            return response()->json(['message' => 'Payment intent not created'], 401);
        }

        $pi_id = $response->data->id;
        $paymentMethod = $this->createPaymentMethod($secret, $pi_id, $return_url);

        return ['pi_id' => $pi_id, 'paymentMethod' => $paymentMethod];
    }

    public function createPaymentMethod($secret, $paymentIntentId, $return_url)
    {
        $base64 = base64_encode($secret . ':');

        $response = $this->sendCurlRequest(
            "https://api.paymongo.com/v1/payment_methods",
            "POST",
            $base64,
            [
                'data' => [
                    'attributes' => [
                        'type' => 'gcash'
                    ]
                ]
            ]
        );

        $response = json_decode($response, false);

        if (isset($response->data->id)) {
            return $this->attachPaymentIntent($secret, $paymentIntentId, $response->data->id, $return_url);
        } else {
            return response()->json(['message' => 'Payment method not created'], 401);
        }
    }

    public function attachPaymentIntent($secret, $paymentIntentId, $paymentMethodId, $return_url)
    {
        $base64 = base64_encode($secret . ':');

        $response = $this->sendCurlRequest(
            "https://api.paymongo.com/v1/payment_intents/$paymentIntentId/attach",
            "POST",
            $base64,
            [
                'data' => [
                    'attributes' => [
                        'payment_method' => $paymentMethodId,
                        'return_url' => $return_url,
                    ]
                ]
            ]
        );

        $response = json_decode($response, false);

        if (isset($response->data->attributes->next_action->redirect->url)) {
            return $response->data->attributes->next_action->redirect->url;
        } else {
            return response()->json(['message' => 'Payment intent not attached'], 401);
        }
    }

    private function sendCurlRequest($url, $method, $base64, $payload)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "authorization: Basic $base64",
                "content-type: application/json",
            ],
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            throw new \Exception("cURL Error: $error");
        }

        return $response;
    }
}
