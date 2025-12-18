<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * List supported payment methods
     */
    public function methods()
    {
        return response()->json([
            'methods' => [
                ['code' => 'ecocash', 'name' => 'EcoCash'],
                ['code' => 'onemoney', 'name' => 'OneMoney'],
                ['code' => 'zipit', 'name' => 'Zipit'],
            ],
        ]);
    }

    /**
     * Initiate PayNow payment
     */
    public function initiate(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:ecocash,onemoney,zipit,paynow',
            'phone' => 'required_if:payment_method,ecocash|required_if:payment_method,onemoney|string|max:15',
        ]);

        $order = Order::findOrFail($data['order_id']);

        // Ownership check
        if ($order->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Prevent double payment
        if (Payment::where('order_id', $order->id)->where('status', 'completed')->exists()) {
            return response()->json(['message' => 'Order already paid'], 400);
        }

        $transactionId = (string) Str::uuid();

        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => $order->total,
            'payment_method' => $data['payment_method'],
            'transaction_id' => $transactionId,
            'status' => 'pending',
        ]);

        // PayNow request (FORM DATA)
        $response = Http::asForm()->post(config('services.paynow.endpoint'), [
            'id' => config('services.paynow.integration_id'),
            'key' => config('services.paynow.integration_key'),
            'amount' => $payment->amount,
            'reference' => $transactionId,
            'additionalinfo' => 'Order ' . ($order->order_number ?? $order->id),
            'returnurl' => config('services.paynow.return_url'),
            'resulturl' => config('services.paynow.result_url'),
            'authemail' => auth()->user()->email ?? 'payments@yourapp.co.zw',
            'method' => $data['payment_method'],
            'phone' => $data['phone'] ?? null,
        ]);

        if (!$response->successful()) {
            $payment->update([
                'status' => 'failed',
                'gateway_response' => $response->body(),
            ]);

            return response()->json(['message' => 'Payment initiation failed'], 500);
        }

        $gatewayData = $response->json();

        $payment->update([
            'gateway_response' => $gatewayData,
        ]);

        return response()->json([
            'payment_id' => $payment->id,
            'transaction_id' => $transactionId,
            'payment_method' => $payment->payment_method,
            'poll_url' => $gatewayData['pollurl'] ?? null,
            'redirect_url' => $gatewayData['redirecturl'] ?? null,
            'status' => 'pending',
            'message' => 'Please confirm payment on your phone',
        ], 201);
    }

    /**
     * Verify PayNow payment
     */
    public function verify($transactionId)
    {
        $payment = Payment::where('transaction_id', $transactionId)->firstOrFail();

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        if ($payment->status === 'completed') {
            return response()->json([
                'transaction_id' => $payment->transaction_id,
                'status' => 'completed',
            ]);
        }

        $pollUrl = $payment->gateway_response['pollurl'] ?? null;

        if (!$pollUrl) {
            return response()->json(['message' => 'Poll URL missing'], 400);
        }

        $response = Http::get($pollUrl);

        if (!$response->successful()) {
            return response()->json(['message' => 'Verification failed'], 500);
        }

        $result = $response->json();

        $payment->update([
            'gateway_response' => $result,
        ]);

        if (($result['status'] ?? '') === 'Paid') {
            $payment->update(['status' => 'completed']);

            $payment->order->update([
                'payment_status' => 'completed',
                'status' => 'confirmed',
            ]);
        }

        if (in_array(($result['status'] ?? ''), ['Cancelled', 'Failed'])) {
            $payment->update(['status' => 'failed']);
        }

        return response()->json([
            'transaction_id' => $payment->transaction_id,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'order_id' => $payment->order_id,
        ]);
    }

    public function result(Request $request){
        //Paynow server callback handling
        $transactionId = $request->input('reference');

        $payment = Payment::where('transaction_id', $transactionId)->first();

        if(!$payment){
            return response('Ok', 200); //Acknowledge to PayNow even if payment not found
        }

        $payment->update([
            'gateway_response' => $request->all(),
        ]);

        if($request->status ?? '' === 'Paid'){
            $payment->update(['status' => 'completed']);

            $payment->order->update([
                'payment_status' => 'completed',
                'status' => 'confirmed',
            ]);
        }

        if(in_array($request->status ?? '', ['Cancelled', 'Failed'])){
            $payment->update(['status' => 'failed']);
        }

        return response('Ok', 200); //Acknowledge to PayNow
    }
}
