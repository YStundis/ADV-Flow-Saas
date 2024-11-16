<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\referral_payout;

class ReferralController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (Auth::user()->type == 'company') {

            $commissioncompany = User::join('referral_transactions', 'users.id', '=', 'referral_transactions.user_id')->select('users.name as company_name', 'referral_transactions.*', 'plans.*')
                ->join('plans', 'plans.id', '=', 'referral_transactions.plan')
                ->where('users.use_refercode', Auth::user()->id)
                ->get();

            $commissionTotal = User::join('referral_transactions', 'users.id', '=', 'referral_transactions.user_id')
                ->where('users.use_refercode', Auth::user()->id)
                ->sum('referral_transactions.commisson_amount');

            $paidTotal = referral_payout::where('user_id', Auth::user()->id)
                ->where('status', 'APPROVED')
                ->sum('payout_amount');

            $payout = referral_payout::join('users', 'users.id', '=', 'referral_payouts.user_id')
                ->where('user_id', Auth::user()->id)
                ->get();

            return view('referral.index', compact('commissionTotal', 'commissioncompany', 'paidTotal', 'payout'));
        } else {

            $commissioncompany = User::join('referral_transactions', 'users.id', '=', 'referral_transactions.user_id')->select('users.name as company_name', 'users.id as user_id', 'referral_transactions.*', 'users.*', 'plans.*')
                ->join('plans', 'plans.id', '=', 'referral_transactions.plan')
                ->get();

            $payout = referral_payout::join('users', 'users.id', '=', 'referral_payouts.user_id')->select('users.id as user_id', 'users.name as company_name', 'referral_payouts.*')
                ->where('status', 'PENDING')
                ->get();

            $company = User::where('type', 'company')->get();
            return view('referral.admin', compact('commissioncompany', 'payout', 'company'));
        }

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function savereferralSettings(Request $request)
    {
        if ($request->referral_status) {
            $validator = Validator::make(
                $request->all(),
                [
                    'commission_percentage' => 'required|string|max:180',
                    'minimum_threshold_amount' => 'required|string|max:180',
                    'guidelines' => 'required|string|max:180',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }
        }

        $post = $request->all();
        unset($post['_token']);
        if ($request->referral_status) {
            $referral_status = 'on';
        } else {
            $referral_status = 'off';
        }
        $post['referral_status'] = $referral_status;
        foreach ($post as $key => $data) {
            $arr = [
                $data,
                $key,
                Auth::user()->id,
            ];

            DB::insert(
                'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                $arr
            );
        }

        return redirect()->back()->with('success', __('Referral Setting successfully updated.'));
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
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
