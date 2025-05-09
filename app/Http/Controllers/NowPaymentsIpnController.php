<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Nowpay;

class NowPaymentsIpnController extends Controller
{

    public function __invoke(Request $request)
    {
        $data = $request->all();
        $signature = $request->header('x-nowpayments-sig');
        ksort($data);
        $payload = json_encode($data);
        $calculatedSignature = hash_hmac('sha512', $payload, config('nowpayments.ipn_secret_key'));
        if (!hash_equals($calculatedSignature, $signature)) {
            return response(status: 200);
        }
        $order = Nowpay::where('uuid', $data['order_id'])->first();
        if (!$order)  return response(status: 200);
        $order->update([
            'remote_status' => $data['payment_status'],
        ]);
        if ($data['payment_status'] === 'finished') {
            try {
                $order->complete();
            } catch (\Exception $e) {
                return response(status: 200);
            }
        }
        return response(status: 200);
    }
}
