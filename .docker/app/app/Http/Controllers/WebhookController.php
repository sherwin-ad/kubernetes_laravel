<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Webhook;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Webhook $webhook)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Webhook $webhook)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Webhook $webhook)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Webhook $webhook)
    {
        //
    }

    public function event(Request $request)
    {
        $payload = $request->getContent();
        $webhook = new WebhookEvent();
        $webhook->payload = $payload;
        $webhook->save();

        // match payment_intent_id to transaction pi_id column
        // update transaction status to paid

        // decode payload
        $payload = json_decode($payload, true);

        // Validate payload
        if (
            !isset($payload['data']['attributes']['data']['attributes']['payment_intent_id']) ||
            !isset($payload['data']['attributes']['data']['attributes']['status'])
        ) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $payment_intent_id = $payload['data']['attributes']['data']['attributes']['payment_intent_id'];
        $payment_status = $payload['data']['attributes']['data']['attributes']['status'];

        $transaction = Transaction::where('pi_id', $payment_intent_id)->first();
        if ($transaction) {
            $transaction->payment_status = $payment_status;
            $transaction->save();

            // push to aggregator the payment status
            // get aggregator
            $aggregator = $transaction->aggregator;
            $callback_url = $aggregator->callback_url;

            // send payment status to aggregator curl
            $curl = curl_init();

            $body = [
                'trx_no' => $transaction->trx_no,
                'status' => $payment_status,
            ];

            curl_setopt_array($curl, [
                CURLOPT_URL => $callback_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($body),
                CURLOPT_HTTPHEADER => [
                    "accept: application/json",
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

        return response()->json(['message' => 'Webhook received']);
    }
}
