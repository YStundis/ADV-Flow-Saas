<?php

namespace App\Http\Controllers;

use App\Models\Cases;
use App\Models\ToDo;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ToDoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        if (Auth::user()->can('manage todo')) {
            if ($request->filter == 'All' || empty($request->filter)) {
                $todo_tmp = ToDo::where('created_by', Auth::user()->creatorId())->get();
            } else {
                $todo_tmp = ToDo::where('created_by', Auth::user()->creatorId())->where('priority', $request->filter)->get();
            }

            $user = Auth::user()->id;
            if (Auth::user()->type == 'client') {

                $todo = [];
                foreach ($todo_tmp as $value) {
                    $case = Cases::find($value->relate_to);
                    $data = json_decode($case->your_party_name);
                    foreach ($data as $key => $val) {
                        if (isset($val->clients) && $val->clients == $user) {
                            $todo[$value->id] = $value;
                        }
                    }
                }

            }elseif(Auth::user()->type == 'advocate') {

                $todo = [];
                foreach ($todo_tmp as $value) {
                    $data = explode(',', $value->assign_to);
                    if (isset($data) && in_array($user, $data)) {
                        $todo[$value->id] = $value;
                    }

                }

                $todo_tmp = ToDo::where('assign_by', Auth::user()->id)->get();
                foreach ($todo_tmp as $value) {
                    $todo[$value->id] = $value;
                }
                // dd($todo);
            } else {
                $todo = $todo_tmp;
            }

            $priorities = [
                'All',
                'Urgent',
                'High',
                'Medium',
                'Low',
            ];

            $todos = [];
            foreach ($priorities as $value) {
                foreach ($todo as $ke => $val) {
                    if ($value == $val->priority) {
                        if ($value == 'Urgent') {
                            $p_id = 1;
                        } elseif ($value == 'High') {
                            $p_id = 2;
                        } elseif ($value == 'Medium') {
                            $p_id = 3;
                        } elseif ($value == 'Low') {
                            $p_id = 4;
                        } else {
                            $p_id = 0;
                        }

                        $val->p_id = $p_id;
                        $todos[] = $val;
                    }
                }
            }
            $curr_time = strtotime(date("Y-m-d h:i:s"));

            // UPCOMING
            $upcoming_todo = [];

            foreach ($todos as $key => $utd) {
                $start_date = strtotime($utd->start_date);
                if ($start_date > $curr_time && $utd->status == 1) {
                    if ($utd->priority == 'Urgent') {
                        $p_id = 1;
                    } elseif ($utd->priority == 'High') {
                        $p_id = 2;
                    } elseif ($utd->priority == 'Medium') {
                        $p_id = 3;
                    } elseif ($utd->priority == 'Low') {
                        $p_id = 4;
                    } else {
                        $p_id = 0;
                    }


                    $upcoming_todo[$key]['id'] = $utd->id;
                    $upcoming_todo[$key]['title'] = $utd->title;
                    $upcoming_todo[$key]['description'] = $utd->description;
                    $upcoming_todo[$key]['due_date'] = $utd->due_date;
                    $upcoming_todo[$key]['relate_to'] = $utd->relate_to;
                    $upcoming_todo[$key]['assign_to'] = $utd->assign_to;
                    $upcoming_todo[$key]['assign_by'] = $utd->assign_by;
                    $upcoming_todo[$key]['status'] = $utd->status;
                    $upcoming_todo[$key]['priority'] = $utd->priority;
                    $upcoming_todo[$key]['p_id'] = $p_id;
                }
            }

            // PENDING
            $pending_todo = [];

            foreach ($todos as $key => $ptd) {
                $start_date = strtotime($ptd->start_date);

                if ($start_date < $curr_time && $ptd->status == 1) {
                    if ($ptd->priority == 'Urgent') {
                        $p_id = 1;
                    } elseif ($ptd->priority == 'High') {
                        $p_id = 2;
                    } elseif ($ptd->priority == 'Medium') {
                        $p_id = 3;
                    } elseif ($ptd->priority == 'Low') {
                        $p_id = 4;
                    } else {
                        $p_id = 0;
                    }
                    $pending_todo[$key]['id'] = $ptd->id;
                    $pending_todo[$key]['title'] = $ptd->title;
                    $pending_todo[$key]['description'] = $ptd->description;
                    $pending_todo[$key]['due_date'] = $ptd->due_date;
                    $pending_todo[$key]['relate_to'] = $ptd->relate_to;
                    $pending_todo[$key]['assign_to'] = $ptd->assign_to;
                    $pending_todo[$key]['assign_by'] = $ptd->assign_by;
                    $pending_todo[$key]['status'] = $ptd->status;
                    $pending_todo[$key]['priority'] = $ptd->priority;
                    $pending_todo[$key]['p_id'] = $p_id;

                }
            }

            // complted
            $complted = [];

            foreach ($todos as $key => $ctd) {
                if ($ctd->status == 0) {
                    if ($ctd->priority == 'Urgent') {
                        $p_id = 1;
                    } elseif ($ctd->priority == 'High') {
                        $p_id = 2;
                    } elseif ($ctd->priority == 'Medium') {
                        $p_id = 3;
                    } elseif ($ctd->priority == 'Low') {
                        $p_id = 4;
                    } else {
                        $p_id = 0;
                    }
                    $complted[$key]['id'] = $ctd->id;
                    $complted[$key]['title'] = $ctd->title;
                    $complted[$key]['description'] = $ctd->description;
                    $complted[$key]['due_date'] = $ctd->due_date;
                    $complted[$key]['relate_to'] = $ctd->relate_to;
                    $complted[$key]['assign_to'] = $ctd->assign_to;
                    $complted[$key]['assign_by'] = $ctd->assign_by;
                    $complted[$key]['completed_by'] = $ctd->completed_by;
                    $complted[$key]['completed_at'] = $ctd->completed_at;
                    $complted[$key]['status'] = $ctd->status;
                    $complted[$key]['priority'] = $ctd->priority;
                    $complted[$key]['p_id'] = $p_id;

                }
            }

            // $complted = ToDo::where('created_by', Auth::user()->creatorId())->where('status', 0)->get();


            return view('todo.index', compact('todos', 'upcoming_todo', 'pending_todo', 'complted', 'priorities'));
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
        if (Auth::user()->can('create todo')) {

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

            // $cases = Cases::where('created_by', Auth::user()->creatorId())->get()->pluck('title', 'id');
            // $teams = User::where('created_by',Auth::user()->creatorId())->where('type','!=','advocate')->get()->pluck('name', 'id');
            // $teams = User::where('type', '!=', 'super admin')->where('type', '!=', 'client')->where('type', '!=', 'company')
            //     ->where(function ($query) {
            //         $query->where('created_by', Auth::user()->creatorId())->orWhere('id', Auth::user()->creatorId());
            //     })->pluck('name', 'id');

            $priorities = ToDo::priorities();
            return view('todo.create', compact('cases', 'priorities'));
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
        if (Auth::user()->can('create todo')) {

            $validator = Validator::make(
                $request->all(),
                [
                    'due_date' => 'required',
                    'relate_to' => 'required',
                    'assigned_date' => 'required',
                    'priority' => 'required',
                    'title' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $todo = new ToDo();
            $todo['title'] = $request->title;
            $todo['description'] = $request->description;
            $todo['due_date'] = $request->due_date;
            $todo['start_date'] = $request->assigned_date;
            $todo['end_date'] = $request->due_date;
            $todo['relate_to'] =
            $todo['assign_to'] = !empty($request->assign_to) ? implode(',', $request->assign_to) : '';
            $todo['assign_by'] = Auth::user()->id;
            $todo['priority'] = $request->priority;
            $todo['created_by'] = Auth::user()->creatorId();
            $todo->save();


            if ($request->get('is_check') == '1') {
                $type = 'task';
                $request1 = new ToDo();
                $request1->title = $request->description;
                $request1->start_date = $request->assigned_date;
                $request1->end_date = $request->due_date;
                Utility::addCalendarData($request1, $type);
            }

            return redirect()->route('to-do.index')->with('success', __('To-Do successfully created.'));

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
        if (Auth::user()->can('view todo')) {

            $todo = ToDo::find($id);

            return view('todo.view', compact('todo'));


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
        $todo = ToDo::find($id);

        if (Auth::user()->can('edit todo')) {

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

            $teams = [];
            $case_id = $todo->relate_to;
            $case = Cases::find($case_id);
            $data = explode(',', $case->advocates);
            $team = User::whereIn('id', $data)->get();
            if($team){
                foreach ($team as $value) {
                    $teams[$value->id] = $value->name;
                }
            }

            $team = User::where('created_by', Auth::user()->creatorId())->where('type', '!=', 'client')->where('type', '!=', 'advocate')->get();
            if($team){
                foreach ($team as $value) {
                    $teams[$value->id] = $value->name;
                }
                }

            // $cases = Cases::where('created_by', Auth::user()->creatorId())->get()->pluck('title', 'id');

            // $teams = User::where('type', '!=', 'super admin')->where('type', '!=', 'client')->where('type', '!=', 'company')
            //     ->where(function ($query) {
            //         $query->where('created_by', Auth::user()->creatorId())->orWhere('id', Auth::user()->creatorId());
            //     })->pluck('name', 'id');
            // $relate_to = Cases::whereIn('id', explode(',', $todo->relate_to))
            //     ->where(function ($query) {
            //         $query->where('created_by', Auth::user()->creatorId())->orWhere('id', Auth::user()->creatorId());
            //     })->get();

            $assign_to = User::whereIn('id', explode(',', $todo->assign_to))->get();
            $priorities = ToDo::priorities();
            return view('todo.edit', compact('todo', 'cases', 'teams', 'assign_to', 'priorities'));

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
        if (Auth::user()->can('edit todo')) {

            $validator = Validator::make(
                $request->all(),
                [
                    'due_date' => 'required',
                    'relate_to' => 'required',
                    'assigned_date' => 'required',
                    'priority' => 'required',
                    'title' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }


            $todo = ToDo::find($id);
            $todo['title'] = $request->title;
            $todo['description'] = $request->description;
            $todo['due_date'] = $request->due_date;
            $todo['start_date'] = $request->assigned_date;
            $todo['end_date'] = $request->due_date;
            // $todo['relate_to'] = implode(',', $request->relate_to);
            $todo['relate_to'] = $request->relate_to;
            $todo['assign_to'] = !empty($request->assign_to) ? implode(',', $request->assign_to) : '';
            $todo['assign_by'] = Auth::user()->id;
            $todo['priority'] = $request->priority;
            $todo->save();

            return redirect()->route('to-do.index')->with('success', __('To-Do successfully updated.'));

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
        if (Auth::user()->can('delete todo')) {
            $todo = ToDo::find($id);
            $todo->delete();
            return redirect()->route('to-do.index')->with('success', __('You have successfully deleted the to-do.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

    }

    public function status($id)
    {
        if (Auth::user()->can('edit todo')) {
            $todo = ToDo::find($id);

            return view('todo.status', compact('todo'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

    }

    public function statusUpdate($id)
    {
        if (Auth::user()->can('edit todo')) {

            $todo = ToDo::find($id);
            if ($todo->status == 0) {
                return redirect()->route('to-do.index')->with('error', __('This to-do already marked as completed.'));
            }

            if ($todo->status == 1) {
                $todo->status = 0;
                $todo->completed_at = date("d-m-y h:i");
                $todo->completed_by = Auth::user()->id;
                $todo->save();
            }
            return redirect()->route('to-do.index')->with('success', __('You have successfully completed the to-dos.'));

        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

    }

    public function assgineadvocate(Request $request)
    {
        $teams = [];
        // dd($request->all());
        $case_id = $request->case_id;
        $case = Cases::find($case_id);
        $data = explode(',', $case->advocates);
        $team = User::whereIn('id', $data)->get();
        foreach ($team as $value) {
            $teams[$value->id] = $value->name;
        }

        $team = User::where('created_by', Auth::user()->creatorId())->where('type', '!=', 'client')->where('type', '!=', 'advocate')->get();
        foreach ($team as $value) {
            $teams[$value->id] = $value->name;
        }

        // dd($teams);

        if (isset($team) && !empty($team)) {
            return response()->json(['status' => true, 'message' => "team get success", 'data' => $teams])->setStatusCode(200);
        }
    }

    public function assgineclient(Request $request)
    {
        $teams = [];
        // dd($request->all());
        $case_id = $request->case_id;
        $case = Cases::find($case_id);
        $data = json_decode($case->your_party_name);
        foreach ($data as $key => $val) {
            if (isset($val->clients)) {
                $team = User::where('id', $val->clients)->get();
                foreach ($team as $v) {
                    $teams[$v->id] = $v->name;
                }
            }
        }

        // dd($teams);

        if (isset($team) && !empty($team)) {
            return response()->json(['status' => true, 'message' => "team get success", 'data' => $teams])->setStatusCode(200);
        }
    }

}
