<?php

namespace App\Http\Controllers;

use App\Models\CaseType;
use Google\Service\AlertCenter\User;
use Illuminate\Http\Request;

class CaseTypeController extends Controller
{
    public function index()
    {
        // dd(\Auth::user()->creatorId());
        $casetype= CaseType::where('created_by',\Auth::user()->creatorId())->get();
        return view('casetypes.index',compact('casetype'));
    }

    public function create()
    {
        return view('casetypes.create');
    }

    public function store(Request $request)
    {
        if(\Auth::user()->can('create casetype'))
        {
            $validation = [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],
            ];
            $request->validate($validation);
            $created_by = \Auth::user()->creatorId();
            $casetype = new CaseType();
            $casetype->name = $request->name;
            $casetype->created_by = $created_by;
            $casetype->save();

            return redirect()->route('casetype.index')->with('success', __('Case Type created successfully'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(CaseType $caseType)
    {
        //
    }

    public function edit(CaseType $caseType,$id)
    {
        $casetype = CaseType::find($id);
        return view('casetypes.edit',compact('casetype'));
    }

    public function update(Request $request, CaseType $caseType,$id)
    {
        if(\Auth::user()->can('edit casetype'))
        {
            $validation = [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],
            ];
            $request->validate($validation);

            $casetype        = CaseType::find($id);
            $casetype->name  = $request->name;
            $casetype->created_by = \Auth::user()->creatorId();
            $casetype->save();

            return redirect()->route('casetype.index')->with('success', __('Case Type updated successfully'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(CaseType $caseType,$id)
    {
        if(\Auth::user()->can('delete casetype'))
        {
            $casetype = CaseType::find($id);
            $casetype->delete();

            return redirect()->route('casetype.index')->with('success', __('Case Type deleted successfully'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
