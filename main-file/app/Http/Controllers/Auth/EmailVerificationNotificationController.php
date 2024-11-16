<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Utility;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME);
        }
        $settings = Utility::settings(1);

        config([
            'mail.default'                   => isset($settings['mail_driver'])       ? $settings['mail_driver']       : '',
            'mail.mailers.smtp.host'         => isset($settings['mail_host'])         ? $settings['mail_host']         : '',
            'mail.mailers.smtp.port'         => isset($settings['mail_port'])         ? $settings['mail_port']         : '',
            'mail.mailers.smtp.encryption'   => isset($settings['mail_encryption'])   ? $settings['mail_encryption']   : '',
            'mail.mailers.smtp.username'     => isset($settings['mail_username'])     ? $settings['mail_username']     : '',
            'mail.mailers.smtp.password'     => isset($settings['mail_password'])     ? $settings['mail_password']     : '',
            'mail.from.address'              => isset($settings['mail_from_address']) ? $settings['mail_from_address'] : '',
            'mail.from.name'                 => isset($settings['mail_from_name'])    ? $settings['mail_from_name']    : '',
        ]);
        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Something Went Wrong...'));
        }
        return back()->with('status', 'verification-link-sent');
    }
}
