<?php

namespace App\Http\Controllers;

use App\Models\Aggregator;
use App\Models\Merchant;
use App\Models\Webhook;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class MerchantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $model = Merchant::query()->orderBy('created_at', 'desc');

            return DataTables::eloquent($model)

                ->addColumn('action', function ($merchant) {
                    // get all aggregators with id
                    return view('merchant._action', compact(['merchant']));
                })
                ->editColumn('aggregator_id', function ($merchant) {
                    return $merchant->aggregator->name ?? 'Not Assigned';
                })
                ->addColumn('daily_transaction_limit', function ($merchant) {
                    // sum from transactions with peso sign
                    return '₱' . number_format($merchant->dtl, 2);
                })
                ->addColumn('running_day_amount', function ($merchant) {
                    // sum from transactions with peso sign for the current day
                    $today = now()->startOfDay();
                    $amount = $merchant->transactions()->where('created_at', '>=', $today)->sum('amount');
                    return '₱' . number_format($amount, 2);
                })
                ->addColumn('bulk_checkbox', function ($item) {
                    // return view('partials._bulk_checkbox',compact('item'));
                })
                ->addIndexColumn()
                ->toJson();
        }

        $data['aggregators'] = Aggregator::all();
        return view('merchant.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('merchant.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:merchants,email',
            'api_key' => 'required|unique:merchants,api_key',
            'api_secret' => 'required',
        ], [
            'name.required' => 'Merchant name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email is invalid.',
            'api_key.required' => 'API Key is required.',
            'api_secret.required' => 'API Secret is required'
        ]);

        $merchant = Merchant::create($request->all());

        // CREATE WEBHOOK
        $response = $this->createWebhook($merchant->id, $request->api_secret);

        if ($response) {
            $response = json_decode($response);
            if (isset($response->data->attributes)) {
                $webhook = new Webhook();
                $webhook->merchant_id = $merchant->id;
                $webhook->url = $response->data->attributes->url;
                $webhook->secret = $response->data->attributes->secret_key;
                $webhook->save();
            } else {
                return redirect()->route('merchant.index')
                    ->with('error', 'Failed to create webhook.');
            }
        }

        return redirect()->route('merchant.index')
            ->with('success', 'Merchant created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Merchant $merchant)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Merchant $merchant)
    {
        return view('merchant.edit', compact('merchant'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Merchant $merchant)
    {
        // validate the request...
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:merchants,email,' . $merchant->id,
            'api_key' => 'required|unique:merchants,api_key,' . $merchant->id,
            'api_secret' => 'required'
        ], [
            'name.required' => 'Merchant name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email is invalid.',
            'api_key.required' => 'API Key is required.',
            'api_secret.required' => 'API Secret is required'
        ]);

        $merchant->update($request->all());

        // CREATE WEBHOOK IF NOT EXIST
        $webhook = Webhook::where('merchant_id', $merchant->id)->first();
        if (!$webhook) {
            $response = $this->createWebhook($merchant->id, $request->api_secret);

            if ($response) {
                $response = json_decode($response);
                if (isset($response->data->attributes)) {
                    $webhook = new Webhook();
                    $webhook->merchant_id = $merchant->id;
                    $webhook->url = $response->data->attributes->url;
                    $webhook->secret = $response->data->attributes->secret_key;
                    $webhook->save();
                } else {
                    return redirect()->route('merchant.index')
                        ->with('error', 'Failed to create webhook.');
                }
            }
        }

        return redirect()->route('merchant.index')
            ->with('success', 'Merchant updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Merchant $merchant)
    {
        if ($merchant->delete()) {
            return redirect()->route('merchant.index')
                ->with('success', 'Merchant deleted successfully.');
        } else {
            return redirect()->route('merchant.index')
                ->with('error', 'Merchant could not be deleted.');
        }
    }

    public function assign(Request $request)
    {
        // validate the request...
        //
        $merchant = Merchant::findOrfail($request->merchant_id);
        $merchant->aggregator_id = $request->aggregator_id;
        $merchant->save();
        return redirect()->route('merchant.index')
            ->with('success', 'Aggregator assigned successfully.');
    }

    public function unassign(Request $request, $id)
    {
        // validate the request...
        //
        $merchant = Merchant::findOrfail($id);
        $merchant->aggregator_id = null;
        $merchant->save();
        return redirect()->route('merchant.index')
            ->with('success', 'Aggregator unassigned successfully.');
    }

    public function createWebhook($merchant, $secret)
    {
        $base64 = base64_encode($secret . ':');

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.paymongo.com/v1/webhooks",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
                'data' => [
                    'attributes' => [
                        'url' => url('/webhook/event?m=' . $merchant),
                        'events' => [
                            'payment.paid'
                        ]
                    ]
                ]
            ]),
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "authorization: Basic $base64",
                "content-type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }
}
