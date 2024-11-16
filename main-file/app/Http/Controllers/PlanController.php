<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Plan;
use App\Models\PlanRequest;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Auth::user()->can('manage plan') || Auth::user()->can('buy plan')) {
            if (Auth::user()->type == 'super admin') {
                $plans = Plan::all();
            } else {
                $plans = Plan::where('status', 1)->get();
            }

            $payment_setting = Utility::set_payment_settings();
            $settings = Utility::settings(Auth::user()->id);

            return view('plan.index', compact('plans', 'payment_setting'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (Auth::user()->can('create plan')) {
            $arrDuration = Plan::$arrDuration;

            return view('plan.create', compact('arrDuration'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (Auth::user()->can('create plan')) {
            $admin_payment_setting = Utility::set_payment_settings();

            if (!empty($admin_payment_setting) && ($admin_payment_setting['is_stripe_enabled'] == 'on' || $admin_payment_setting['is_paypal_enabled'] == 'on' || $admin_payment_setting['is_paystack_enabled'] == 'on' || $admin_payment_setting['is_flutterwave_enabled'] == 'on' || $admin_payment_setting['is_razorpay_enabled'] == 'on' || $admin_payment_setting['is_mercado_enabled'] == 'on' || $admin_payment_setting['is_paytm_enabled'] == 'on' || $admin_payment_setting['is_mollie_enabled'] == 'on' || $admin_payment_setting['is_skrill_enabled'] == 'on' || $admin_payment_setting['is_coingate_enabled'] == 'on' || $admin_payment_setting['is_paymentwall_enabled'] == 'on' || $admin_payment_setting['is_manually_enabled'] == 'on' || $admin_payment_setting['is_bank_enabled'] == 'on' || $admin_payment_setting['is_paytab_enabled'] == 'on')) {
                $validator = Validator::make(
                    $request->all(),
                    [
                        'name' => 'required|unique:plans',
                        'price' => 'required|numeric|min:0',
                        'duration' => 'required',
                        'max_users' => 'required|numeric',
                        'max_advocates' => 'required|numeric',
                        'storage_limit' => 'required|numeric',
                    ]
                );

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }

                $post = $request->all();

                if ($request->trial == 1) {
                    $post['trial_days'] = !empty($request->trial_days) ? $request->trial_days : 0;
                }
                if (!isset($request->enable_chatgpt)) {
                    $post['enable_chatgpt'] = 'off';
                } else {
                    $post['enable_chatgpt'] = 'on';
                }
                $post['status'] = 1;

                if (Plan::create($post)) {
                    return redirect()->back()->with('success', __('Plan Successfully created.'));
                } else {
                    return redirect()->back()->with('error', __('Something is wrong.'));
                }

            } else {
                return redirect()->back()->with('error', __('Please set stripe or paypal api key & secret key for add new plan.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (Auth::user()->can('edit plan')) {
            $arrDuration = Plan::$arrDuration;
            $plan = Plan::find($id);

            return view('plan.edit', compact('plan', 'arrDuration'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $plan_id)
    {
        if (Auth::user()->can('edit plan')) {
            $payment = Utility::set_payment_settings();

            if (count($payment) > 0) {
                $plan = Plan::find($plan_id);
                if (!empty($plan)) {
                    if ($plan->id == '1') {
                        $validation = [];
                        $validation['name'] = 'required|unique:plans,name,' . $plan_id;
                        $validation['price'] = 'required|numeric|min:0';
                        $validation['max_users'] = 'required|numeric';
                        $validation['max_advocates'] = 'required|numeric';
                        $validation['storage_limit'] = 'required|numeric';
                    } else {
                        $validation = [];
                        $validation['name'] = 'required|unique:plans,name,' . $plan_id;
                        $validation['price'] = 'required|numeric|min:0';
                        $validation['duration'] = 'required';
                        $validation['max_users'] = 'required|numeric';
                        $validation['max_advocates'] = 'required|numeric';
                        $validation['storage_limit'] = 'required|numeric';
                    }
                    $request->validate($validation);
                    $post = $request->all();

                    $post['trial'] = !empty($request->trial) ? $request->trial : 0;
                    if ($request->trial == 1) {

                        $post['trial_days'] = !empty($request->trial_days) ? $request->trial_days : 0;
                    }
                    if (!isset($request->enable_chatgpt)) {
                        $post['enable_chatgpt'] = 'off';
                    } else {
                        $post['enable_chatgpt'] = 'on';
                    }
                    if ($plan->update($post)) {
                        return redirect()->back()->with('success', __('Plan Successfully updated.'));
                    } else {
                        return redirect()->back()->with('error', __('Something is wrong.'));
                    }
                } else {
                    return redirect()->back()->with('error', __('Plan not found.'));
                }
            } else {
                return redirect()->back()->with('error', __('Please set payment api key & secret key for update plan'));
            }

        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $plan = Plan::find($id);
        $usersCount = User::where('plan', $plan->id)->count();

        if ($usersCount == 0) {
            $plan->delete();
            return redirect()->back()->with('success', __('Plan successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('The company has subscribed to this plan, so it cannot be deleted.'));
        }

    }

    public function payment($code)
    {
        $plan_id = \Illuminate\Support\Facades\Crypt::decrypt($code);
        $plan = Plan::find($plan_id);
        $planReqs = PlanRequest::where('user_id', Auth::user()->id)->where('plan_id', $plan_id)->first();

        if ($plan) {
            $admin_payment_setting = Utility::payment_settings();
            return view('payment', compact('plan', 'admin_payment_setting', 'planReqs'));
        } else {
            return redirect()->back()->with('error', __('Plan is deleted.'));
        }
    }

    public function PlanTrial($id)
    {
        if (Auth::user()->type != 'super admin') {
            try {
                $id = Crypt::decrypt($id);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Plan Not Found.'));
            }
            $plan = Plan::find($id);
            $user = User::where('id', Auth::user()->id)->first();

            if (!empty($plan->trial) == 1) {

                $user->assignPlan($plan->id, 'Trial', $user->id);
                $user->is_trial_done = 1;
                $user->save();
            }

            return redirect()->back()->with('success', 'Your trial has been started.');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

    }

    public function updateStatus(Request $request)
    {
        $planId = $request->input('plan_id');

        $plan = Plan::find($planId);
        $usersCount = User::where('plan', $plan->id)->count();
        if ($usersCount == 0) {

            $plan->status = !$plan->status;
            $plan->save();
            return response()->json([
                'success' => true,
                'message' => 'Plan status updated successfully',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'The company has subscribed to this plan, so it cannot be deleted',
            ]);

        }

    }
    public function Refund($id, $orderId)
    {

        $order = Order::find($orderId);
        $order->refund = 1;
        $order->save();

        $objUser = User::find($id);
        $objUser->assignPlan(1);
        return redirect()->back()->with('success', __('Plan refund successfully.'));
    }
}
