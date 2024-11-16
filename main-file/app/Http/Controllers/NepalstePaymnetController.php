<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Plan;
use App\Models\UserCoupon;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Models\Bill;
use App\Models\User;
use App\Models\BillPayment;

class NepalstePaymnetController extends Controller
{
    public function invoiceGetNepalsteCancel(Request $request)
    {
        return redirect()->back()->with('error', __('Transaction has failed'));
    }

    public function invoicePayWithNepalste(Request $request, $invoice_id)
    {
        try {
            $invoice_id = Crypt::decrypt($invoice_id);
            // dd($request->all(), $invoice_id);

            $invoice = Bill::find($invoice_id);
            $user = User::where('id', $invoice->created_by)->first();
            $get_amount = $request->amount;

            // $customers      = Customer::find($invoice->customer_id);
            // $comapnysetting = Utility::getCompanyPaymentSetting($invoice->created_by);

            $payment_setting = Utility::getCompanyPaymentSetting($user->id);
            $api_key = isset($payment_setting['nepalste_public_key']) ? $payment_setting['nepalste_public_key'] : '';

            // $setting        = Utility::settingsById($invoice->created_by);
            $order_id = strtoupper(str_replace('.', '', uniqid('', true)));
            $request->validate(['amount' => 'required|numeric|min:0']);

            $parameters = [
                'identifier' => 'DFU80XZIKS',
                'currency' => isset($payment_setting['site_currency']) ? $payment_setting['site_currency'] : 'USD',
                'amount' => $get_amount,
                'details' => $invoice->id,
                'ipn_url' => route('invoice.nepalste.status', [$invoice_id, $get_amount]),
                'cancel_url' => route('invoice.nepalste.cancel'),
                'success_url' => route('invoice.nepalste.status', [$invoice_id, $get_amount]),
                'public_key' => $api_key,
                'site_logo' => 'https://nepalste.com.np/assets/images/logoIcon/logo.png',
                'checkout_theme' => 'dark',
                'customer_name' => $user->name,
                'customer_email' => $user->email,
            ];

            //live end point
            $liveUrl = "https://nepalste.com.np/payment/initiate";
            //test end point
            $sandboxUrl = "https://nepalste.com.np/sandbox/payment/initiate";

            $url = $payment_setting['nepalste_mode'] == 'live' ? $liveUrl : $sandboxUrl;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($result, true);

            if (isset($result['success'])) {
                return redirect($result['url']);
            } else {
                return redirect()->back()->with('error', __($result['message']));
            }

        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function invoiceGetNepalsteStatus(Request $request, $invoice_id, $getAmount)
    {
        $invoice = Bill::find($invoice_id);
        $user = User::where('id', $invoice->created_by)->first();
        $payment_setting = Utility::getCompanyPaymentSetting($user->id);

        $invoice_payment = new BillPayment();
        $invoice_payment->bill_id = $invoice->id;
        $invoice_payment->txn_id = app('App\Http\Controllers\BillController')->transactionNumber($user->id);
        $invoice_payment->amount = $getAmount;
        $invoice_payment->date = date('Y-m-d');
        $invoice_payment->method = __('Neplaste');
        $invoice_payment->save();

        $payment = BillPayment::where('bill_id', $invoice->id)->sum('amount');

        if ($payment >= $invoice->total_amount) {
            $invoice->status = 'PAID';
            $invoice->due_amount = 0.00;
        } else {
            $invoice->status = 'Partialy Paid';
            $invoice->due_amount = $invoice->due_amount - $getAmount;
        }
        $invoice->save();

        if (Auth::check()) {
            return redirect()->route('bills.show', $invoice->id)->with('success', __('Payment successfully added'));
        } else {
            return redirect()->back()->with('success', __(' Payment successfully added.'));
        }
    }

    public function planPayWithNepalste(Request $request)
    {

        $authuser = Auth::user();
        $payment_setting = Utility::payment_settings();
        $api_key = isset($payment_setting['nepalste_public_key']) ? $payment_setting['nepalste_public_key'] : '';
        $currency = isset($payment_setting['currency']) ? $payment_setting['currency'] : '';
        $planID = Crypt::decrypt($request->plan_id);

        $plan = Plan::find($planID);
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

        if ($plan) {

            $plan_amount = $plan->price;

            if (!empty($request->coupon)) {
                $coupons = Coupon::where('code', strtoupper($request->coupon))->where('is_active', '1')->first();
                if (!empty($coupons)) {
                    $usedCoupun = $coupons->used_coupon();
                    $discount_value = ($plan->price / 100) * $coupons->discount;
                    $plan_amount = $plan->price - $discount_value;

                    if ($coupons->limit == $usedCoupun) {
                        return redirect()->back()->with('error', __('This coupon code has expired.'));
                    }
                } else {
                    return redirect()->back()->with('error', __('This coupon code is invalid or has expired.'));
                }
            }
            if ($plan_amount <= 0) {
                $authuser = \Auth::user();
                $authuser->plan = $plan->id;
                $authuser->save();

                $assignPlan = $authuser->assignPlan($plan->id);
                if ($assignPlan['is_success'] == true && !empty($plan)) {
                    if (!empty($authuser->payment_subscription_id) && $authuser->payment_subscription_id != '') {
                        try {
                            $authuser->cancel_subscription($authuser->id);
                        } catch (\Exception $exception) {
                            \Log::debug($exception->getMessage());
                        }
                    }
                    $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

                    $userCoupon = new UserCoupon();
                    $userCoupon->user = $authuser->id;
                    $userCoupon->coupon = $coupons->id;
                    $userCoupon->order = $orderID;
                    $userCoupon->save();

                    Order::create(
                        [
                            'order_id' => $orderID,
                            'name' => null,
                            'email' => null,
                            'card_number' => null,
                            'card_exp_month' => null,
                            'card_exp_year' => null,
                            'plan_name' => $plan->name,
                            'plan_id' => $plan->id,
                            'price' => $plan_amount == null ? 0 : $plan_amount,
                            'price_currency' => !empty($payment_setting['currency']) ? $payment_setting['currency'] : 'USD',
                            'txn_id' => '',
                            'payment_type' => 'Nepalste',
                            'payment_status' => 'Succeeded',
                            'receipt' => null,
                            'user_id' => $authuser->id,
                        ]
                    );
                    $assignPlan = $authuser->assignPlan($plan->id);
                    return redirect()->route('plans.index')->with('success', __('Plan Successfully Activated'));
                }
            }
        }

        if (!empty($request->coupon)) {
            $response = ['plan_amount' => $plan_amount, 'plan' => $plan, 'coupon' => $request->coupon];
        } else {
            $response = ['plan_amount' => $plan_amount, 'plan' => $plan];
        }

        $parameters = [
            'identifier' => 'DFU80XZIKS',
            'currency' => $currency,
            'amount' => $plan_amount,
            'details' => $plan->name,
            'ipn_url' => route('plan.nepalste.status', $response),
            'cancel_url' => route('plan.nepalste.cancel'),
            'success_url' => route('plan.nepalste.status', $response),
            'public_key' => $api_key,
            'site_logo' => 'https://nepalste.com.np/assets/images/logoIcon/logo.png',
            'checkout_theme' => 'dark',
            'customer_name' => $authuser->name,
            'customer_email' => $authuser->email,
        ];

        //live end point
        $liveUrl = "https://nepalste.com.np/payment/initiate";
        //test end point
        $sandboxUrl = "https://nepalste.com.np/sandbox/payment/initiate";

        $url = $payment_setting['nepalste_mode'] == 'live' ? $liveUrl : $sandboxUrl;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($result, true);

        if (isset($result['success'])) {
            return redirect($result['url']);
        } else {
            return redirect()->back()->with('error', __($result['message']));
        }
    }

    public function planGetNepalsteStatus(Request $request)
    {

        $payment_setting = Utility::payment_settings();

        $currency = isset($payment_setting['currency']) ? $payment_setting['currency'] : '';
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
        $getAmount = $request->plan_amount;
        $authuser = \Auth::user();
        $plan = Plan::find($request->plan);

        if (isset($request->coupon) && !empty($request->coupon)) {
            $coupons = Coupon::where('code', $request->coupon)->where('is_active', '1')->first();
            // dd($coupons);
            if (!empty($coupons)) {

                $userCoupon = new UserCoupon();
                $userCoupon->user = $authuser->id;
                $userCoupon->coupon = $coupons->id;
                $userCoupon->order = $orderID;
                $userCoupon->save();
                $usedCoupun = $coupons->used_coupon();
                if ($coupons->limit <= $usedCoupun) {

                    $coupons->is_active = 0;
                    $coupons->save();
                }
                $discount_value = ($plan->price / 100) * $coupons->discount;
                $getAmount = $plan->price - $discount_value;

            }
        }

        $order = new Order();
        $order->order_id = $orderID;
        $order->name = $authuser->name;
        $order->card_number = '';
        $order->card_exp_month = '';
        $order->card_exp_year = '';
        $order->plan_name = $plan->name;
        $order->plan_id = $plan->id;
        $order->price = $getAmount;
        $order->price_currency = $currency;
        $order->txn_id = $orderID;
        $order->payment_type = __('Neplaste');
        $order->payment_status = 'Succeeded';
        $order->txn_id = '';
        $order->receipt = '';
        $order->user_id = $authuser->id;
        $order->save();

        $assignPlan = $authuser->assignPlan($plan->id);

        if ($assignPlan['is_success']) {
            Utility::referralcommisonadd($plan->id);
            return redirect()->route('plans.index')->with('success', __('Plan activated Successfully.'));
        } else {
            return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
        }
    }

    public function planGetNepalsteCancel(Request $request)
    {
        return redirect()->back()->with('error', __('Transaction has failed'));
    }
}
