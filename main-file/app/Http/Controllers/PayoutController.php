<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\referral_payout;
use App\Models\User;
use App\Models\Utility;

class PayoutController extends Controller
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
        $commissionTotal = User::join('referral_transactions', 'users.id', '=', 'referral_transactions.user_id')
            ->where('users.use_refercode', Auth::user()->id)
            ->sum('referral_transactions.commisson_amount');

        $paidTotal = referral_payout::where('user_id', Auth::user()->id)
            ->whereIn('status', ['APPROVED', 'PENDING'])
            ->sum('payout_amount');


        $valideamount = $commissionTotal - $paidTotal;

        return view('referral.create', compact('valideamount'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'request_amount' => 'required|string|max:255',
            ]
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        $commissionTotal = User::join('referral_transactions', 'users.id', '=', 'referral_transactions.user_id')
            ->where('users.use_refercode', Auth::user()->id)
            ->sum('referral_transactions.commisson_amount');

        $paidTotal = referral_payout::where('user_id', Auth::user()->id)
            ->whereIn('status', ['APPROVED', 'PENDING'])
            ->sum('payout_amount');


        $valideamount = $commissionTotal - $paidTotal;

        $setting = Utility::settings();
        $post = $request->all();
        // dd($commissionTotal,$paidTotal,$valideamount);
        if ($post['request_amount'] <= $valideamount && $post['request_amount'] > $setting['minimum_threshold_amount']) {

            referral_payout::create([
                'user_id' => Auth::user()->id,
                'payout_amount' => $post['request_amount'],
                'status' => 'PENDING',
            ]);
            return redirect()->back()->with('success', __('Your Requsted amount successfully send.'));
        } else {
            $messages = "Your Requsted amount are not valide.";
            return redirect()->back()->with('error', $messages);
        }


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
    public function update(Request $request, $id, $status)
    {
        // dd($id);
        if ($status == 1) {
            $Payout = referral_payout::find($id);
            $Payout->status = 'APPROVED';
            $Payout->save();
            return redirect()->back()->with('success', __('Request Approve Successfully.'));
        }
        if ($status == 0) {
            $Payout = referral_payout::find($id);
            $Payout->status = 'REJECTED';
            $Payout->save();
            return redirect()->back()->with('success', __('Request Rejected Successfully.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
