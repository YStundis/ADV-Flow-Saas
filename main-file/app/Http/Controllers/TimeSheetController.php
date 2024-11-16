<?php

namespace App\Http\Controllers;

use App\Exports\TimesheetsExport;
use App\Models\Cases;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class TimeSheetController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Auth::user()->can('manage timesheet')) {

            $timesheets_tmp = Timesheet::where('created_by',Auth::user()->creatorId())->get();

            $user = Auth::user()->id;

            if (Auth::user()->type == 'client') {

                $timesheets = [];
                foreach ($timesheets_tmp as $value) {
                    $case = Cases::find($value->case);
                    $data = json_decode($case->your_party_name);
                    foreach ($data as $key => $val) {
                        if (isset($val->clients) && $val->clients == $user) {
                            $timesheets[$value->id] = $value;
                        }
                    }
                }

            }elseif (Auth::user()->type == 'advocate') {

                $timesheets = [];
                foreach ($timesheets_tmp as $value) {
                    $case = Cases::find($value->case);
                    $data = explode(',', $case->advocates);
                    if (isset($data) && in_array($user, $data)) {
                        $timesheets[$value->id] = $value;
                    }

                }
                // dd($timesheets);
            } else {
                $timesheets = $timesheets_tmp;
            }

            // $timesheets = Timesheet::where('created_by',Auth::user()->creatorId())->get();
            return view('timesheet.index',compact('timesheets'));

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
        if (Auth::user()->can('manage timesheet')) {

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
            // $members = User::where('created_by',Auth::user()->creatorId())->where('type','advocate')->get()->pluck('name', 'id');

            return view('timesheet.create',compact('cases'));

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
        if (Auth::user()->can('create timesheet')) {
            $validator = Validator::make(
                $request->all(), [
                    'case' => 'required',
                    'date' => 'required',
                    'particulars' => 'required',
                    'member' => 'required',
                    'time' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $timesheet = new Timesheet();
            $timesheet['case'] = $request->case;
            $timesheet['date'] = $request->date;
            $timesheet['particulars'] = $request->particulars;
            $timesheet['time'] = $request->time;
            $timesheet['member'] = $request->member;
            $timesheet['created_by'] = Auth::user()->creatorId();
            $timesheet->save();
            return redirect()->route('timesheet.index')->with('success', __('Timesheet successfully created.'));

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
        if (Auth::user()->can('view timesheet')) {
            $cases = Cases::get()->pluck('title', 'id');
            $members = User::get()->pluck('name', 'id');
            $timesheet = Timesheet::find($id);
            return view('timesheet.view', compact('cases', 'members', 'timesheet'));

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
        if (Auth::user()->can('edit timesheet')) {

            $timesheet = Timesheet::find($id);
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

            $case_id = $timesheet->case;
            $case = Cases::find($case_id);
            $data = explode(',', $case->advocates);
            $members = [];
            $team = User::whereIn('id', $data)->get();
            if($team){
                foreach ($team as $value) {
                    $members[$value->id] = $value->name;
                }
            }

            $team = User::where('created_by', Auth::user()->creatorId())->where('type', '!=', 'client')->where('type', '!=', 'advocate')->get();
            if($team){
                foreach ($team as $value) {
                    $members[$value->id] = $value->name;
                }
            }


            // $cases = Cases::where('created_by',Auth::user()->creatorId())->get()->pluck('title', 'id');
            // $members = User::where('created_by',Auth::user()->creatorId())->where('type','advocate')->get()->pluck('name', 'id');
            return view('timesheet.edit', compact('cases', 'members','timesheet'));

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
        if (Auth::user()->can('edit timesheet')) {
            $validator = Validator::make(
                $request->all(), [
                    'case' => 'required',
                    'date' => 'required',
                    'particulars' => 'required',
                    'member' => 'required',
                    'time' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $timesheet = Timesheet::find($id);
            $timesheet['case'] = $request->case;
            $timesheet['date'] = $request->date;
            $timesheet['particulars'] = $request->particulars;
            $timesheet['time'] = $request->time;
            $timesheet['member'] = $request->member;
            $timesheet['created_by'] = Auth::user()->id;
            $timesheet->save();

            return redirect()->route('timesheet.index')->with('success', __('Timesheet successfully updated.'));

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
        if (Auth::user()->can('edit timesheet')) {
            $timesheet = Timesheet::find($id);
            if ($timesheet) {
                $timesheet->delete();
            }
            return redirect()->route('timesheet.index')->with('success', __('Timesheet successfully deleted.'));

        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));

        }

    }

    public function exportFile()
    {
        $name = 'timesheets_' . date('Y-m-d i:h:s');
        $data = Excel::download(new TimesheetsExport(), $name . '.xlsx');
        ob_end_clean();
        return $data;
    }
}
