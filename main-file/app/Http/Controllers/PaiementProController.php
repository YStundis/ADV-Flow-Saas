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

class PaiementProController extends Controller
{
    public function invoicePayWithPaiementPro(Request $request)
    {
        $invoice_id = $request->invoice_id;
        $invoice = Bill::find($invoice_id);
        $user = User::where('id', $invoice->created_by)->first();
        $get_amount = $request->amount;

        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

        try {
            if ($invoice) {

                $payment_setting = Utility::getCompanyPaymentSetting($user->id);
                $merchant_id = isset($payment_setting['paiementpro_merchant_id']) ? $payment_setting['paiementpro_merchant_id'] : '';
                $currency = isset($payment_setting['site_currency']) ? $payment_setting['site_currency'] : 'RUB';
                $response = ['orderId' => $orderID, 'user' => $user, 'get_amount' => $get_amount, 'invoice' => $invoice, 'currency' => $currency];

                $params = [
                    'external_id' => $orderID,
                    'payer_email' => Auth::user()->email ?? 'Testuser@gmail.com',
                    'description' => 'Payment for order ' . $orderID,
                    'amount' => $get_amount,
                    'invoice_id' => $invoice_id,
                ];

                $call_back = route('invoice.paiementpro.status');
                $data = array(
                    'merchantId' => $merchant_id,
                    'amount' => $get_amount,
                    'description' => "Api PHP",
                    'channel' => $request->channel,
                    'countryCurrencyCode' => $currency,
                    'referenceNumber' => "REF-" . time(),
                    'customerEmail' => $user->email,
                    'customerFirstName' => $user->name,
                    'customerLastname' => $user->name,
                    'customerPhoneNumber' => $request->mobile_number,
                    'notificationURL' => $call_back,
                    'returnURL' => $call_back,
                    'returnContext' => json_encode($params),
                );

                $data = json_encode($data);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://www.paiementpro.net/webservice/onlinepayment/init/curl-init.php");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_HEADER, FALSE);
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                $response = curl_exec($ch);
                curl_close($ch);
                $response = json_decode($response);

                if (isset($response->success) && $response->success == true) {
                    return redirect($response->url);
                } else {
                    return redirect()->back()->with('error', __('Something went wrong'));
                }
            } else {
                return redirect()->back()->with('error', 'Invoice not found.');
            }
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e));
        }
    }

    public function invoiceGetPaiementproStatus(Request $request)
    {
        $dataArray = json_decode($request->returnContext, true);
        $invoice = Bill::find($dataArray['invoice_id']);
        $user = User::where('id', $invoice->created_by)->first();
        $payment_setting = Utility::getCompanyPaymentSetting($user->id);

        if ($request->responsecode == 0) {
            $invoice_payment = new BillPayment();
            $invoice_payment->bill_id = $invoice->id;
            $invoice_payment->txn_id = app('App\Http\Controllers\BillController')->transactionNumber($user->id);
            $invoice_payment->amount = $dataArray['amount'];
            $invoice_payment->date = date('Y-m-d');
            $invoice_payment->method = 'Paiementpro';
            $invoice_payment->save();

            $payment = BillPayment::where('bill_id', $invoice->id)->sum('amount');

            if ($payment >= $invoice->total_amount) {
                $invoice->status = 'PAID';
                $invoice->due_amount = 0.00;
            } else {
                $invoice->status = 'Partialy Paid';
                $invoice->due_amount = $invoice->due_amount - $dataArray['amount'];
            }
            $invoice->save();
            if (Auth::check()) {
                return redirect()->route('bills.show', $invoice->id)->with('success', __('Payment successfully added'));
            } else {
                return redirect()->back()->with('success', __(' Payment successfully added.'));
            }
        }else{
            return redirect()->back()->with('error', __('Transaction fail'));;
        }
    }

    // plan
    public function planPayWithpaiementpro(Request $request)
    {
        $user = Auth::user();

        $payment_setting = Utility::payment_settings();
        $merchant_id = isset($payment_setting['paiementpro_merchant_id']) ? $payment_setting['paiementpro_merchant_id'] : '';
        $currency = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
        $planID = Crypt::decrypt($request->plan_id);

        $plan = Plan::find($planID);

        if ($plan) {
            $plan_amount = $plan->price;
            if (isset($request->coupon) && !empty($request->coupon)) {
                $coupons = Coupon::where('code', $request->coupon)->where('is_active', '1')->first();
                if (!empty($coupons)) {

                    $usedCoupun = $coupons->used_coupon();
                    $discount_value = ($plan_amount / 100) * $coupons->discount;
                    $plan->discounted_price = $plan_amount - $discount_value;

                    if ($coupons->limit <= $usedCoupun) {
                        $coupons->is_active = 0;
                        $coupons->save();
                    }

                    $plan_amount = $plan_amount - $discount_value;
                    $coupon_id = $coupons->id;
                }
            }

            if ($plan_amount <= 0) {

                $user->plan = $plan->id;
                $user->save();

                $assignPlan = $user->assignPlan($plan->id, $user->id, $request->paystack_payment_frequency);

                if ($assignPlan['is_success'] == true && !empty($plan)) {

                    $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

                    Order::create(
                        [
                            'order_id' => $orderID,
                            'name' => $user->name,
                            'email' => $user->email,
                            'card_number' => null,
                            'card_exp_month' => null,
                            'card_exp_year' => null,
                            'plan_name' => $plan->name,
                            'plan_id' => $plan->id,
                            'price' => $plan_amount,
                            'price_currency' => !empty($currency) ? $currency : 'USD',
                            'txn_id' => '',
                            'payment_type' => 'Paiementpro',
                            'payment_status' => 'Succeeded',
                            'receipt' => null,
                            'user_id' => $user->id,
                        ]
                    );

                    $userCoupon = new UserCoupon();
                    $userCoupon->user = $user->id;
                    $userCoupon->coupon = $coupons->id;
                    $userCoupon->order = $orderID;
                    $userCoupon->save();

                    return redirect()->route('plans.index')->with('success', __('Plan successfully upgraded.'));
                } else {
                    return response()->json(["success" => false, 'msg' => __('Plan fail to upgrade.')]);
                }
            }

        }

        try {
            $call_back = route('plan.paiementpro.status', [$plan->id,]);
            $data = array(
                'merchantId' => $merchant_id,
                'amount' => $plan_amount,
                'description' => "Api PHP",
                'channel' => $request->channel,
                'countryCurrencyCode' => $currency,
                'referenceNumber' => "REF-" . time(),
                'customerEmail' => $user->email,
                'customerFirstName' => $user->name,
                'customerLastname' => $user->name,
                'customerPhoneNumber' => $request->mobile_number,
                'notificationURL' => $call_back,
                'returnURL' => $call_back,
                'returnContext' => json_encode(['coupon' => $request->coupon]),
            );

            $data = json_encode($data);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://www.paiementpro.net/webservice/onlinepayment/init/curl-init.php");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            $response = curl_exec($ch);
            curl_close($ch);
            $response = json_decode($response);
            if (isset($response->success) && $response->success == true) {
                return redirect($response->url);
            } else {
                return redirect()->back()->with('error', __('Something went wrong'));
            }

        } catch (Exception $e) {
            return redirect()->back()->with('error', $e);
        }
    }

    public function planGetpaiementproStatus(Request $request, $plan_id)
    {
        $payment_setting = Utility::payment_settings();
        $currency = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
        $user = Auth::user();
        $plan = Plan::find($plan_id);

        $jsonData = $request->returnContext;
        $dataArray = json_decode($jsonData, true);

        if ($plan) {

            $plan_amount = $plan->price;
            $order_id = strtoupper(str_replace('.', '', uniqid('', true)));
            $user = Auth::user();

            try {
                if ($request->responsecode == 0) {

                    if (isset($dataArray['coupon']) && !empty($dataArray['coupon'])) {
                        $coupons = Coupon::where('code', $dataArray['coupon'])->where('is_active', '1')->first();

                        if (!empty($coupons)) {

                            $userCoupon = new UserCoupon();
                            $userCoupon->user = $user->id;
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

                    $order = Order::create(
                        [
                            'order_id' => $order_id,
                            'name' => !empty($user->name) ? $user->name : '',
                            'email' => !empty($user->email) ? $user->email : '',
                            'card_number' => null,
                            'card_exp_month' => null,
                            'card_exp_year' => null,
                            'plan_name' => !empty($plan->name) ? $plan->name : 'Plan',
                            'plan_id' => $plan->id,
                            'price' => !empty($plan_amount) ? $plan_amount : 0,
                            'price_currency' => $currency,
                            'txn_id' => '',
                            'payment_type' => __('Paiement Pro'),
                            'payment_status' => 'Succeeded',
                            'receipt' => null,
                            'user_id' => $user->id,
                        ]
                    );
                    $assignPlan = $user->assignPlan($plan->id, $request->payment_frequency);

                    if ($assignPlan['is_success']) {
                        Utility::referralcommisonadd($plan->id);
                        return redirect()->route('plans.index')->with('success', __('Plan activated Successfully!'));
                    } else {
                        return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
                    }
                } else {
                    return redirect()->back()->with('error', __('Transaction Unsuccesfull'));
                }
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Transaction Unsuccesfull'));
        }
    }
}
