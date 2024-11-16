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

class CinetPayController extends Controller
{

    public function planPayWithCinetPay(Request $request)
    {
        $authuser = Auth::user();
        $payment_setting = Utility::payment_settings();
        $currency = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
        $planID = Crypt::decrypt($request->plan_id);
        $plan = Plan::find($planID);
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

        if ($plan) {

            $plan_amount = $plan->price;

            if (!empty($request->coupon && !empty($request->coupon))) {
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

                // $authuser = \Auth::user();
                $authuser->plan = $plan->id;
                $authuser->save();

                $assignPlan = $authuser->assignPlan($plan->id);

                if ($assignPlan['is_success'] == true && !empty($plan)) {

                    Order::create(
                        [
                            'order_id' => $orderID,
                            'name' => $authuser->name,
                            'email' => $authuser->email,
                            'card_number' => null,
                            'card_exp_month' => null,
                            'card_exp_year' => null,
                            'plan_name' => $plan->name,
                            'plan_id' => $plan->id,
                            'price' => $plan_amount == null ? 0 : $plan_amount,
                            'price_currency' => !empty($currency) ? $currency : 'USD',
                            'txn_id' => '',
                            'payment_type' => 'Cinetpay',
                            'payment_status' => 'Succeeded',
                            'receipt' => null,
                            'user_id' => $authuser->id,
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

                if (
                    $currency != 'XOF' &&
                    $currency != 'CDF' &&
                    $currency != 'USD' &&
                    $currency != 'KMF' &&
                    $currency != 'GNF'
                ) {
                    return redirect()->route('plans.index')->with('error', __('Availabe currencies: XOF, CDF, USD, KMF, GNF'));
                }

                $cinetpay_data = [
                    "amount" => $plan_amount,
                    "currency" => $currency,
                    "apikey" => $payment_setting['cinetpay_api_key'],
                    "site_id" => $payment_setting['cinetpay_site_id'],
                    "transaction_id" => $orderID,
                    "description" => "Plan Subscription",
                    "return_url" => route('plan.cinetpay.return'),
                    "notify_url" => route('plan.cinetpay.notify'),
                    "metadata" => "user001",
                    'customer_name' => isset($authuser->name) ? $authuser->name : '',
                    'customer_surname' => isset($authuser->name) ? $authuser->name : '',
                    'customer_email' => isset($authuser->email) ? $authuser->email : '',
                    'customer_phone_number' => '',
                    'customer_address' => '',
                    'customer_city' => 'texas',
                    'customer_country' => 'BF',
                    'customer_state' => 'USA',
                    'customer_zip_code' => '',
                ];

                $curl = curl_init();

                curl_setopt_array(
                    $curl,
                    array(
                        CURLOPT_URL => 'https://api-checkout.cinetpay.com/v2/payment',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 45,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => json_encode($cinetpay_data),
                        CURLOPT_SSL_VERIFYPEER => 0,
                        CURLOPT_HTTPHEADER => array(
                            "content-type:application/json"
                        ),
                    )
                );
                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);

                $response_body = json_decode($response, true);
                // dd($response_body);
                if (isset($response_body['code']) && $response_body['code'] == '201') {
                    $cinetpaySession = [
                        'order_id' => $orderID,
                        'plan_id' => $plan->id,
                        'plan_amount' => $plan_amount,
                        'coupon' => !empty($request->coupon) ? $request->coupon : '',
                    ];

                    $request->session()->put('cinetpaySession', $cinetpaySession);

                    $payment_link = $response_body["data"]["payment_url"];
                    return redirect($payment_link);
                } else {
                    return back()->with('error', isset($response_body["description"]) ? $response_body["description"] : 'Something Went Wrong!!!');
                }
            } catch (\Exception $e) {
                return redirect()->route('plans.index')->with('error', $e->getMessage());
            }
        } else {
            return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
        }
    }

    public function planCinetPayReturn(Request $request)
    {

        $cinetpaySession = $request->session()->get('cinetpaySession');
        $request->session()->forget('cinetpaySession');


        $payment_setting = Utility::payment_settings();
        $currency = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
        $plan = Plan::find($cinetpaySession['plan_id']);
        $authuser = Auth::user();
        $plan_amount = $cinetpaySession['plan_amount'];
        $order_id = $cinetpaySession['order_id'];

        if (isset($request->transaction_id) || isset($request->token)) {

            $cinetpay_check = [
                "apikey" => $payment_setting['cinetpay_api_key'],
                "site_id" => $payment_setting['cinetpay_site_id'],
                "transaction_id" => $request->transaction_id
            ];

            $response = $this->getPayStatus($cinetpay_check);

            $response_body = json_decode($response, true);

            // dd($order_id, $response_body['code'], $plan_amount);

            if ($response_body['code'] == '00') {

                if (isset($cinetpaySession['coupon']) && !empty($cinetpaySession['coupon'])) {
                    $coupons = Coupon::where('code', $cinetpaySession['coupon'])->where('is_active', '1')->first();
                    // dd($coupons);
                    if (!empty($coupons)) {
                        $plan_amount = $plan->price;
                        $userCoupon = new UserCoupon();
                        $userCoupon->user = $authuser->id;
                        $userCoupon->coupon = $coupons->id;
                        $userCoupon->order = $order_id;
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
                        'order_id' => $order_id,
                        'name' => $authuser->name,
                        'email' => $authuser->email,
                        'card_number' => null,
                        'card_exp_month' => null,
                        'card_exp_year' => null,
                        'plan_name' => !empty($plan->name) ? $plan->name : 'Basic Plan',
                        'plan_id' => $plan->id,
                        'price' => !empty($plan_amount) ? $plan_amount : 0,
                        'price_currency' => !empty($currency) ? $currency : 'USD',
                        'txn_id' => '',
                        'payment_type' => __('Cinetpay'),
                        'payment_status' => 'Succeeded',
                        'receipt' => null,
                        'user_id' => $authuser->id,
                    ]
                );

                $assignPlan = $authuser->assignPlan($plan->id);

                if ($assignPlan['is_success']) {
                    Utility::referralcommisonadd($plan->id);
                    return redirect()->route('plans.index')->with('success', __('Plan activated Successfully!'));
                } else {
                    return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
                }

            } else {

                return redirect()->route('plans.index')->with('error', __('Your Payment has failed!'));
            }
        } else {
            return redirect()->route('plans.index')->with('error', __('Your Payment has failed!'));
        }
    }

    public function planCinetPayNotify(Request $request, $id = null)
    {
        /* 1- Recovery of parameters posted on the URL by CinetPay
         * https://docs.cinetpay.com/api/1.0-fr/checkout/notification#les-etapes-pour-configurer-lurl-de-notification
         * */
        if (isset($request->cpm_trans_id)) {
            // Using your transaction identifier, check that the order has not yet been processed
            $VerifyStatusCmd = "1"; // status value to retrieve from your database
            if ($VerifyStatusCmd == '00') {
                //The order has already been processed
                // Scarred you script
                die();
            }
            if ($id == null) {

                $payment_setting = Utility::getAdminPaymentSetting();

            } else {

                $comapnysetting = Utility::getCompanyPaymentSetting($id);

            }

            /* 2- Otherwise, we check the status of the transaction in the event of a payment attempt on CinetPay
             * https://docs.cinetpay.com/api/1.0-fr/checkout/notification#2-verifier-letat-de-la-transaction */
            $cinetpay_check = [
                "apikey" => $payment_setting['cinetpay_api_key'],
                "site_id" => $payment_setting['cinetpay_site_id'],
                "transaction_id" => $request->cpm_trans_id
            ];

            $response = $this->getPayStatus($cinetpay_check); // call query function to retrieve status

            //We get the response from CinetPay
            $response_body = json_decode($response, true);
            if ($response_body['code'] == '00') {
                /* correct, on délivre le service
                 * https://docs.cinetpay.com/api/1.0-fr/checkout/notification#3-delivrer-un-service*/
                echo 'Congratulations, your payment has been successfully completed';
            } else {
                // transaction a échoué
                echo 'Failure, code:' . $response_body['code'] . ' Description' . $response_body['description'] . ' Message: ' . $response_body['message'];
            }
            // Update the transaction in your database
            /*  $order->update(); */
        } else {
            print ("cpm_trans_id non found");
        }
    }

    public function getPayStatus($data)
    {
        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'https://api-checkout.cinetpay.com/v2/payment/check',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 45,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_HTTPHEADER => array(
                    "content-type:application/json"
                ),
            )
        );
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err)
            return redirect()->route('plans.index')->with('error', __('Something went wrong!'));
        else
            return ($response);
    }

    public function invoicePayWithCinetPay(Request $request)
    {
        try {

            $invoice = Bill::find($request->invoice_id);
            $user = User::where('id', $invoice->created_by)->first();
            $get_amount = $request->amount;

            $customer= User::where('id', $invoice->bill_to)->first();

            $payment_setting = Utility::getCompanyPaymentSetting($user->id);
            $currency = isset($payment_setting['site_currency']) ? $payment_setting['site_currency'] : 'USD';
            $api_key = isset($payment_setting['cinetpay_public_key']) ? $payment_setting['cinetpay_public_key'] : '';


            $order_id = strtoupper(str_replace('.', '', uniqid('', true)));
            $request->validate(['amount' => 'required|numeric|min:100']);

            if (
                $currency != 'XOF' &&
                $currency != 'CDF' &&
                $currency != 'USD' &&
                $currency != 'KMF' &&
                $currency != 'GNF'
            ) {
                return redirect()->route('plans.index')->with('error', __('Availabe currencies: XOF, CDF, USD, KMF, GNF'));
            }

            $cinetpay_data = [
                "amount" => $get_amount,
                "currency" => $currency,
                "apikey" => $payment_setting['cinetpay_api_key'],
                "site_id" => $payment_setting['cinetpay_site_id'],
                "transaction_id" => $order_id,
                "description" => "Invoice Payment",
                "return_url" => route('invoice.cinetpay.return', [$invoice->id, $get_amount]),
                "notify_url" => route('invoice.cinetpay.notify', $invoice->id),
                "metadata" => strval($invoice->id),
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
            ];

            // dd($cinetpay_data);
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api-checkout.cinetpay.com/v2/payment',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 45,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($cinetpay_data),
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_HTTPHEADER => array(
                    "content-type:application/json"
                ),
            )
            );

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            // dd($response);
            $response_body = json_decode($response, true);
            if (isset($response_body['code']) && $response_body['code'] == '201') {
                $cinetpaySession = [
                    'invoice_id' => $request->invoice_id,
                    'get_amount' => $get_amount,
                ];
                $request->session()->put('cinetpaySession', $cinetpaySession);
                $payment_link = $response_body["data"]["payment_url"];
                return redirect($payment_link);
            } else {
                return redirect()->back()->with('error', isset($response_body["description"]) ? $response_body["description"] : 'Something Went Wrong!!!');
            }

        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function invoiceCinetPayReturn(Request $request, $invoice_id, $get_amount)
    {

        $cinetpaySession = $request->session()->get('cinetpaySession');
        $request->session()->forget('cinetpaySession');

        $invoice = Bill::find($cinetpaySession['invoice_id']);
        $user = User::where('id', $invoice->created_by)->first();
        $payment_setting = Utility::getCompanyPaymentSetting($user->id);
        $currency = isset($payment_setting['site_currency']) ? $payment_setting['site_currency'] : 'USD';
        $get_amount = $cinetpaySession['get_amount'];

        // dd($cinetpaySession, $invoice, $currency, $get_amount);

        if (isset($request->transaction_id) || isset($request->token)) {

            $cinetpay_check = [
                "apikey" => $payment_setting['cinetpay_api_key'],
                "site_id" => $payment_setting['cinetpay_site_id'],
                "transaction_id" => $request->transaction_id
            ];

            $response = $this->getPayStatus($cinetpay_check);

            $response_body = json_decode($response, true);
            // dd($response_body);
            if ($response_body['code'] == '00') {

                try {

                    $invoice_payment = new BillPayment();
                    $invoice_payment->bill_id = $invoice->id;
                    $invoice_payment->txn_id = app('App\Http\Controllers\BillController')->transactionNumber($user->id);
                    $invoice_payment->amount = $get_amount;
                    $invoice_payment->date = date('Y-m-d');
                    $invoice_payment->method = __('CinetPay');
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
                        return redirect()->route('bills.show', $invoice->id)->with('success', __('Payment successfully added'));
                    } else {
                        return redirect()->back()->with('success', __(' Payment successfully added.'));
                    }

                } catch (\Throwable $e) {
                    return redirect()->back()->with('error', __($e->getMessage()));
                }
            } else {
                return redirect()->back()->with('error', __('Your Payment has failed!'));
            }
        }
    }

    public function invoiceCinetPayNotify(Request $request, $id = null)
    {
        /* 1- Recovery of parameters posted on the URL by CinetPay
         * https://docs.cinetpay.com/api/1.0-fr/checkout/notification#les-etapes-pour-configurer-lurl-de-notification
         * */
        if (isset($request->cpm_trans_id)) {
            // Using your transaction identifier, check that the order has not yet been processed
            $VerifyStatusCmd = "1"; // status value to retrieve from your database
            if ($VerifyStatusCmd == '00') {
                //The order has already been processed
                // Scarred you script
                die();
            }
            if ($id == null) {

                $payment_setting = Utility::getAdminPaymentSetting();

            } else {

                $comapnysetting = Utility::getCompanyPaymentSetting($id);

            }

            /* 2- Otherwise, we check the status of the transaction in the event of a payment attempt on CinetPay
             * https://docs.cinetpay.com/api/1.0-fr/checkout/notification#2-verifier-letat-de-la-transaction */
            $cinetpay_check = [
                "apikey" => $payment_setting['cinetpay_api_key'],
                "site_id" => $payment_setting['cinetpay_site_id'],
                "transaction_id" => $request->cpm_trans_id
            ];

            $response = $this->getPayStatus($cinetpay_check); // call query function to retrieve status

            //We get the response from CinetPay
            $response_body = json_decode($response, true);
            if ($response_body['code'] == '00') {
                /* correct, on délivre le service
                 * https://docs.cinetpay.com/api/1.0-fr/checkout/notification#3-delivrer-un-service*/
                echo 'Congratulations, your payment has been successfully completed';
            } else {
                // transaction a échoué
                echo 'Failure, code:' . $response_body['code'] . ' Description' . $response_body['description'] . ' Message: ' . $response_body['message'];
            }
            // Update the transaction in your database
            /*  $order->update(); */
        } else {
            print ("cpm_trans_id non found");
        }
    }

}
