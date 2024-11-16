<?php

namespace App\Http\Controllers;

use App\Models\BillPayment;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserCoupon;
use App\Models\Utility;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Models\Bill;
use FedaPay\FedaPay;

class FedapayController extends Controller
{
    public function planPayWithFedapay(Request $request)
    {
        // dd($request->all());
        $authuser           = Auth::user();
        $payment_setting    = Utility::payment_settings();
        $currency           = isset($payment_setting['currency']) ? $payment_setting['currency'] : '';
        $planID             = Crypt::decrypt($request->plan_id);

        $plan       = Plan::find($planID);
        $orderID    = strtoupper(str_replace('.', '', uniqid('', true)));

        if ($plan) {

            $plan_amount    = $plan->price;

            if (!empty($request->coupon)) {

                $coupons = Coupon::where('code', strtoupper($request->coupon))->where('is_active', '1')->first();
                if (!empty($coupons)) {

                    $usedCoupun     = $coupons->used_coupon();
                    $discount_value = ($plan->price / 100) * $coupons->discount;
                    $plan_amount     = $plan->price - $discount_value;

                    if ($coupons->limit == $usedCoupun) {
                        return redirect()->back()->with('error', __('This coupon code has expired.'));
                    }
                } else {
                    return redirect()->back()->with('error', __('This coupon code is invalid or has expired.'));
                }
            }
            if ($plan_amount <= 0) {

                // $authuser = \Auth::user();
                $authuser->plan = $plan->id;
                $authuser->save();

                $assignPlan = $authuser->assignPlan($plan->id);

                if ($assignPlan['is_success'] == true && !empty($plan)) {


                    Order::create(
                        [
                            'order_id'          => $orderID,
                            'name'              => $authuser->name,
                            'email'             => $authuser->email,
                            'card_number'       => null,
                            'card_exp_month'    => null,
                            'card_exp_year'     => null,
                            'plan_name'         => $plan->name,
                            'plan_id'           => $plan->id,
                            'price'             => $plan_amount == null ? 0 : $plan_amount,
                            'price_currency'    => !empty($currency) ? $currency : 'USD',
                            'txn_id'            => '',
                            'payment_type'      => 'Fedapay',
                            'payment_status'    => 'Succeeded',
                            'receipt'           => null,
                            'user_id'           => $authuser->id,
                        ]
                    );

                    $userCoupon = new UserCoupon();
                    $userCoupon->user = $authuser->id;
                    $userCoupon->coupon = $coupons->id;
                    $userCoupon->order = $orderID;
                    $userCoupon->save();


                    // $assignPlan = $authuser->assignPlan($plan->id);
                    return redirect()->route('plans.index')->with('success', __('Plan Successfully Activated'));
                }
            }

            try {

                $fedapay            = !empty($payment_setting['fedapay_secret_key'] ) ? $payment_setting['fedapay_secret_key'] :'';
                $fedapay_mode       = !empty($payment_setting['fedapay_mode']) ? $payment_setting['fedapay_mode'] :'sandbox';
                \FedaPay\FedaPay::setApiKey($fedapay);

                \FedaPay\FedaPay::setEnvironment($fedapay_mode);
                $transaction = \FedaPay\Transaction::create([
                    "description" => "Fedapay Payment",
                    "amount" =>  (int)$plan_amount,
                    "currency" => ["iso" => $currency],

                        "callback_url" => route('plan.fedapay.status', [
                            'plan_id' => $plan->id,
                            'plan_amount' =>$plan_amount,
                            'coupon' => !empty($request->coupon) ? $request->coupon :'' ,
                        ]),
                        "cancel_url" => route('plan.fedapay.status', [
                            'plan_id' => $plan->id,
                            'plan_amount' => $plan_amount,
                            'coupon' => !empty($request->coupon) ? $request->coupon :'' ,
                        ]),

                    ]);

                $token = $transaction->generateToken();
                return redirect($token->url);

            } catch (\Exception $e){
                return redirect()->route('plans.index')->with('error', $e->getMessage());
            }
        } else {
            return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
        }
    }

    public function planGetFedapayStatus(Request $request)
    {
        // dd($request->all());
        try {

            $payment_setting    = Utility::payment_settings();
            $currency           = isset($payment_setting['currency']) ? $payment_setting['currency'] : '';
            $plan = Plan::find($request->plan_id);
            $authuser       = Auth::user();
            $plan_amount = $request->plan_amount;
            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

            if ($request->status == 'approved') {


                if (isset($request->coupon) && !empty($request->coupon)) {
                    $coupons = Coupon::where('code', $request->coupon)->where('is_active', '1')->first();
                    // dd($coupons);
                    if (!empty($coupons)) {
                        $plan_amount = $plan->price;
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
                        $discount_value = ($plan_amount / 100) * $coupons->discount;
                        $plan_amount = $plan_amount - $discount_value;

                    }
                }

                Order::create(
                    [
                        'order_id'          => $orderID,
                        'name'              => !empty($user->name) ? $authuser->name : '',
                        'email'             => !empty($user->email) ? $authuser->email : '',
                        'card_number'       => null,
                        'card_exp_month'    => null,
                        'card_exp_year'     => null,
                        'plan_name'         => !empty($plan->name) ? $plan->name : 'Basic Plan',
                        'plan_id'           => $plan->id,
                        'price'             => !empty($request->plan_amount) ? $request->plan_amount : 0,
                        'price_currency'    => $currency,
                        'txn_id'            => '',
                        'payment_type'      => __('Fedapay'),
                        'payment_status'    => 'Succeeded',
                        'receipt'           => null,
                        'user_id'           => $authuser->id,
                    ]
                );

                $assignPlan = $authuser->assignPlan($plan->id);
                if ($assignPlan['is_success']) {
                    Utility::referralcommisonadd($plan->id);
                    return redirect()->route('plans.index')->with('success', __('Plan activated Successfully.'));
                } else {
                    return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
                }
            } else {
                return redirect()->route('plans.index')->with('error', 'Payment failed.');
            }
        } catch (\Exception $e) {
            return redirect()->route('plans.index')->with('error', $e->getMessage());
        }
    }

    public function invoicePayWithFedapay(Request $request)
    {
        // dd($request->all());
        try {

            $invoice            = Bill::find($request->invoice_id);
            $payment_setting    = Utility::getCompanyPaymentSetting($invoice->created_by);
            $get_amount             = $request->amount;

            $customer= User::where('id', $invoice->bill_to)->first();

            // $setting            = Utility::settingsById($invoice->created_by);
            $currency           = isset($payment_setting['site_currency']) ? $payment_setting['site_currency'] : 'XOF';
            $api_key            = isset($payment_setting['fedapay_secret_key']) ? $payment_setting['fedapay_secret_key'] : '';

            $order_id           = strtoupper(str_replace('.', '', uniqid('', true)));
            $request->validate(['amount' => 'required|numeric|min:0']);

            \FedaPay\FedaPay::setApiKey($api_key);

            // Create Fedapay transaction
            $transaction = \FedaPay\Transaction::create([
                "description"   => "Invoice Payment",
                "amount"        => (int)$get_amount,
                "currency"      => ["iso" => $currency],
                "callback_url"  => route('invoice.fedapay.status', [$invoice->id, $get_amount]),
                "cancel_url"    => route('invoice.fedapay.status', [$invoice->id, $get_amount]),
            ]);
            $token = $transaction->generateToken();

            return redirect($token->url);

        } catch (\Exception $e) {

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function invoiceGetFedapayStatus(Request $request, $invoice_id, $amount)
    {
        try {
            $invoice            = Bill::find($invoice_id);
            $payment_setting    = Utility::getCompanyPaymentSetting($invoice->created_by);
            $currency           = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
            $get_amount = $amount;


            if ($request->status == 'approved') {
                // Payment is successful
                $order_id = strtoupper(str_replace('.', '', uniqid('', true)));

                $invoice_payment = new BillPayment();
                $invoice_payment->bill_id = $invoice->id;
                $invoice_payment->txn_id = '';
                $invoice_payment->amount = $get_amount;
                $invoice_payment->date = date('Y-m-d');
                $invoice_payment->method = __('Fedapay');
                $invoice_payment->save();

                $payment = BillPayment::where('bill_id', $invoice->id)->sum('amount');

                if ($payment >= $invoice->total_amount) {
                    $invoice->status = 'PAID';
                    $invoice->due_amount = 0.00;
                } else {
                    $invoice->status = 'Partialy Paid';
                    $invoice->due_amount = $invoice->due_amount - $get_amount;
                }
                $invoice->save();

                if (Auth::check()) {
                    return redirect()->route('bills.show',$invoice->id)->with('success', __('Payment successfully added.'));
                } else {
                    return redirect()->back()->with('success', __(' Payment successfully added.'));
                }
            } else {
                if (Auth::check()) {
                    return redirect()->route('bills.show', $invoice->id)->with('error', __('Your Payment has failed!'));
                } else {
                    return redirect()->back()->with('error', __('Your Payment has failed!'));
                }
            }
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }
}
