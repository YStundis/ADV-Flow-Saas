<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\Utility;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create($lang = '')
    {
        $settings = Utility::settings();

        if ($settings['signup_button'] == 'on') {
            if ($lang == '') {
                $lang = Utility::getValByName('default_language');
            }

            if ($lang == 'ar' || $lang == 'he') {
                $value = 'on';
            } else {
                $value = 'off';
            }
            DB::insert(
                'insert into settings (`value`, `name`,`created_by`) values ( ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                [
                    $value,
                    'SITE_RTL',
                    1,

                ]
            );

            App::setLocale($lang);
            $ref = request('ref');
            if ($ref) {
                $hasRef = $ref;
                // dd($hasRef);
                $validRef = User::where('create_refercode', $hasRef)->count();
                // dd($validRef);
                if ($validRef > 0) {
                    $refcompany = User::where('create_refercode', $hasRef)->first();
                    $refUserId = $refcompany->id;
                    return view('auth.register', compact('lang', 'refUserId'));
                } else {
                    $refUserId = null;
                    return redirect('/register/' . $lang)->with('Invalidererral', __('Invalide referral code'));
                }
            } else {
                $refUserId = null;
                return view('auth.register', compact('lang', 'refUserId'));
            }
        } else {
            return \Redirect::to('login');
        }

    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $settings = Utility::settings();

        if ($settings['recaptcha_module'] == 'on') {
            $validation['g-recaptcha-response'] = 'required|captcha';
        } else {
            $validation = [];
        }

        $this->validate($request, $validation);
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                Rules\Password::defaults()
            ],
        ]);
        // $refercode = \Str::random(6);
        // $user['create_refercode']=$refercode;

        $length = 6;
        $refercode = '';
        for ($i = 0; $i < $length; $i++) {
            $refercode .= random_int(0, 9);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => 'company',
            'plan' => 1,
            'lang' => Utility::getValByName('default_language'),
            'avatar' => '',
            'created_by' => 1,
            'create_refercode' => $refercode,
            'use_refercode' => $request->refUserId,
        ]);

        // if ($request->refUserId != '') {
        //     // $refercode = \Str::random(6);
        //     $length = 6;
        //     $refercode = '';
        //     for ($i = 0; $i < $length; $i++) {
        //         $refercode .= random_int(0, 9);
        //     }
        //     $user1 = User::find($request->refUserId);
        //     $user1['create_refercode'] = $refercode;
        //     $user1->update();
        // }

        $detail = new UserDetail();
        $detail->user_id = $user->id;
        $detail->save();

        Auth::login($user);

        $settings = Utility::settings();

        if ($settings['email_verification'] == 'on') {
            try {
                Utility::getSMTPDetails(1);

                event(new Registered($user));

                $role_r = Role::findByName('company');
                $user->assignRole($role_r);
                $user->MakeRole($user->id);

            } catch (\Exception $e) {

                $user->delete();
                $lang = Utility::getValByName('default_language');
                return redirect('/register/' . $lang)->with('status', __('Email SMTP settings does not configure so please contact to your site admin.'));
            }
            return view('auth.verify');
        } else {

            $user->email_verified_at = date('h:i:s');
            $user->save();

            $role_r = Role::findByName('company');
            $user->assignRole($role_r);
            $user->MakeRole($user->id);

            return redirect(RouteServiceProvider::HOME);
        }

    }
}
