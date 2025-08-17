<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayRequest;
use App\Services\PayPalService;
use Illuminate\Http\Request;

class PaypalController extends Controller
{
    protected PayPalService $payPalService;
    public function __construct(PayPalService $payPalService){
        $this->payPalService = $payPalService;
    }

    public function pay(PayRequest $request)
    {
        $validated = $request->validated();
        return $this->payPalService->sendPayment($validated['info']);
    }

    public function callback(Request $request)
    {
        return $this->payPalService->callBack($request);
    }

    public function cancel(Request $request)
    {
        return $this->payPalService->cancel($request);
    }
}
