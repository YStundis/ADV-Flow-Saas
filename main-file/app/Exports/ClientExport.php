<?php
namespace App\Exports;


use App\Models\Fee;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClientExport implements FromCollection,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {


        $users = User::where('created_by', '=', Auth::user()->creatorId())
        ->where('type','client')
        ->get();

        foreach ($users as $k => $timesheet) {
            unset(
                $timesheet->email_verified_at,
                $timesheet->password,
                $timesheet->type,
                $timesheet->avatar,
                $timesheet->plan,
                $timesheet->requested_plan,
                $timesheet->plan_expire_date,
                $timesheet->created_by,
                $timesheet->referral_id,
                $timesheet->storage_limit,
                $timesheet->super_admin_employee,
                $timesheet->permission_json,
                $timesheet->default_pipeline,
                $timesheet->remember_token,
                $timesheet->created_at,
                $timesheet->updated_at,
                $timesheet->active_status,
                $timesheet->dark_mode,
                $timesheet->messenger_color,
            );
        }


        return $users;



    }

    public function headings(): array
    {
        return [
            "Id",
            "Name",
            "Email",
            "Language",
            "Is_active",
            "Is_disable",
        ];
    }
}
