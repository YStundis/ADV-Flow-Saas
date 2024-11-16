<?php

namespace App\Http\Controllers;

use App\Exports\FeeExport;
use App\Models\Cases;
use App\Models\Fee;
use App\Models\Plan;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class FeeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Auth::user()->can('manage feereceived')) {
            $fees_tmp = Fee::where('created_by',Auth::user()->creatorId())->get();

            $user = Auth::user()->id;

            if (Auth::user()->type == 'client') {

                $fees = [];
                foreach ($fees_tmp as $value) {
                    $case = Cases::find($value->case);
                    $data = json_decode($case->your_party_name);
                    foreach ($data as $key => $val) {
                        if (isset($val->clients) && $val->clients == $user) {
                            $fees[$value->id] = $value;
                        }
                    }
                }

            }elseif (Auth::user()->type == 'advocate') {
                $fees = [];
                foreach ($fees_tmp as $value) {
                    $case = Cases::find($value->case);
                    $data = explode(',', $case->advocates);
                    if (isset($data) && in_array($user, $data)) {
                        $fees[$value->id] = $value;
                    }
                }
                // dd($fees);
            } else {
                $fees = $fees_tmp;
            }
            return view('fee-receive.index', compact('fees'));

        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));

        }

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (Auth::user()->can('create feereceived')) {

            if (Auth::user()->type == 'client') {

                $user = Auth::user()->id;
                $case = DB::table("cases")
                    ->select("cases.*")
                    ->get();
                $cases = [];
                foreach ($case as $value) {
                    $data = json_decode($value->your_party_name);
                    foreach ($data as $key => $val) {
                        if (isset($val->clients) && $val->clients == $user) {
                            $cases[$value->id] = $value;
                        }
                    }
                }

            }elseif (Auth::user()->type == 'advocate') {
                $user = Auth::user()->id;
                $case = DB::table("cases")
                    ->select("cases.*")
                    ->get();
                $cases = [];
                foreach ($case as $value) {
                    $data = explode(',', $value->advocates);
                    if (isset($data) && in_array($user, $data)) {
                        $cases[$value->id] = $value;
                    }
                }
                // dd($cases);
            } else {
                $cases = Cases::where('created_by', Auth::user()->creatorId())
                    ->get();
            }

            // $cases = Cases::where('created_by',Auth::user()->creatorId())->get()->pluck('title', 'id');
            // $members = User::where('created_by',Auth::user()->creatorId())->where('type','client')->get()->pluck('name', 'id');
            $payments_data = Utility::getCompanyPaymentSetting(Auth::user()->id);
            $payTypes = [
                'Bank Transfer',
                'Cash',
                'Cheque',
                'Online Payment',
            ];

            return view('fee-receive.create', compact('cases', 'payTypes'));

        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));

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
        if (Auth::user()->can('create feereceived')) {

            $validator = Validator::make(
                $request->all(), [
                    'case' => 'required',
                    'date' => 'required',
                    'particulars' => 'required',
                    'member' => 'required',
                    'money' => 'required',
                    'method' => 'required',
                    'notes' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $expense                = new Fee();
            $expense['case']        = $request->case;
            $expense['date']        = $request->date;
            $expense['particulars'] = $request->particulars;
            $expense['money']       = $request->money;
            $expense['member']      = $request->member;
            $expense['method']      = $request->method;
            $expense['notes']       = $request->notes;
            $expense['created_by']  = Auth::user()->creatorId();
            $expense->save();

            return redirect()->route('fee-receive.index')->with('success', __('Data successfully created.'));

        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));

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
        if (Auth::user()->can('view feereceived')) {
            $cases      = Cases::where('created_by',Auth::user()->creatorId())->get()->pluck('title', 'id');
            $members    = User::where('created_by',Auth::user()->creatorId())->get()->pluck('name', 'id');
            $expense    = Fee::find($id);
            return view('fee-receive.view', compact('cases', 'members', 'expense'));

        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));

        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
       if (Auth::user()->can('edit feereceived')) {

        $expense = Fee::find($id);
        if (Auth::user()->type == 'client') {

            $user = Auth::user()->id;
            $case = DB::table("cases")
                ->select("cases.*")
                ->get();
            $cases = [];
            foreach ($case as $value) {
                $data = json_decode($value->your_party_name);
                foreach ($data as $key => $val) {
                    if (isset($val->clients) && $val->clients == $user) {
                        $cases[$value->id] = $value;
                    }
                }
            }

        }elseif (Auth::user()->type == 'advocate') {
            $user = Auth::user()->id;
            $case = DB::table("cases")
                ->select("cases.*")
                ->get();
            $cases = [];
            foreach ($case as $value) {
                $data = explode(',', $value->advocates);

                if (isset($data) && in_array($user, $data)) {
                    $cases[$value->id] = $value;
                }

            }
            // dd($cases);
        } else {
            $cases = Cases::where('created_by', Auth::user()->creatorId())
                ->get();
        }

        $case_id = $expense->case;
        $case = Cases::find($case_id);
        $data = json_decode($case->your_party_name);
        $members = [];
        foreach ($data as $key => $val) {
            if (isset($val->clients)) {
                $team = User::where('id', $val->clients)->get();
                foreach ($team as $v) {
                    $members[$v->id] = $v->name;
                }
            }
        }

        // $cases = Cases::where('created_by',Auth::user()->creatorId())->get()->pluck('title', 'id');
        // $members = User::where('created_by',Auth::user()->creatorId())->where('type','client')->get()->pluck('name', 'id');

        // $expense = Fee::find($id);
        $payments_data = Utility::getCompanyPaymentSetting(Auth::user()->id);
        $payTypes = [
            'Bank Transfer',
            'Cash',
            'Cheque',
            'Online Payment',
        ];

        return view('fee-receive.edit', compact('cases', 'members', 'expense','payTypes'));

    } else {
        return redirect()->back()->with('error', __('Permission Denied.'));

    }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (Auth::user()->can('edit expense')) {
            $validator = Validator::make(
                $request->all(), [
                    'case' => 'required',
                    'date' => 'required',
                    'particulars' => 'required',
                    'member' => 'required',
                    'money' => 'required',
                    'method' => 'required',
                    'notes' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }


            $expense = Fee::find($id);
            $expense['case'] = $request->case;
            $expense['date'] = $request->date;
            $expense['particulars'] = $request->particulars;
            $expense['money'] = $request->money;
            $expense['member'] = $request->member;
            $expense['method'] = $request->method;
            $expense['notes'] = $request->notes;
            $expense->save();

            return redirect()->route('fee-receive.index')->with('success', __('Data successfully updated.'));

        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));

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
        if (Auth::user()->can('delete expense')) {
            $expense = Fee::find($id);
            if ($expense) {
                $expense->delete();
            }
            return redirect()->route('fee-receive.index')->with('success', __('Data successfully deleted.'));

        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));

        }

    }

    public function exportFile()
    {
        $name = 'fees_' . date('Y-m-d i:h:s');
        $data = Excel::download(new FeeExport(), $name . '.xlsx');
        ob_end_clean();
        return $data;
    }
}
