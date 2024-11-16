@php
    $logo=asset(Storage::url('uploads/logo/'));
    $company_logo =App\Models\Utility::getValByName('company_logo');
@endphp
<!DOCTYPE html>
<html>
    <head>
        <style>
        body {
            font-family: Arial, sans-serif;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 25px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        </style>
    </head>
    <body>
        <img alt="" height="auto" width="110" src="{{$logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png')}}" style="border:none;display:block;outline:none;text-decoration:none;height:auto;width:250px; margin:auto" title="" />
        <div class="email-container">
            <p><b>{{ __('Dear ') }}{{ $user->name }},</b></p>   
            <br>
            @if($user->type=='client')
            <p>{{ __('This is a friendly reminder that your payment is due in 7 days. We value your business and kindly ask that you complete the payment by the due date to avoid any inconvenience.') }}</p>
            <br><p><strong>{{ __('Bill Details') }}:</strong></p>
            <ul>
            <li><strong>{{ __('Bill Number') }}:</strong>{{$bill->bill_number}}</li>
            <li><strong>{{ __('Due Date') }}:</strong>{{$bill->due_date}}</li>
            <li><strong>{{ __('Total Amount') }}:</strong>{{$bill->total_amount}}</li>
            </ul>
            <br>
            <p>{{ __('You can make the payment through our secure online portal.') }}</p>
            <br>
            <p>{{ __('If you have already made the payment, please disregard this email. If you have any questions or need assistance, feel free to reach out to our support team.') }}</p>
            <br>
            @else
            <p>{{ __('This is a friendly reminder that you must receive your payment is due in 7 days.  If you not received your payment yet please kindly remind to your client before the due date to avoid any inconvenience.') }}</p>
            <br><p><strong>{{ __('Bill Details') }}:</strong></p>
            <ul>
            <li><strong>{{ __('Bill Number') }}:</strong>{{$bill->bill_number}}</li>
            <li><strong>{{ __('Due Date') }}:</strong>{{$bill->due_date}}</li>
            <li><strong>{{ __('Total Amount') }}:</strong>{{$bill->total_amount}}</li>
            </ul>
            <br>
            <p>{{ __('If you have already received the payment, please disregard this email. If you have any questions or need assistance, feel free to reach out to our support team.') }}</p>
            <br>
            @endif
            <p>{{ __('Thank you for your prompt attention to this matter.') }}</p>
            <br>
            <br>
            
            <p>{{ __('Best regards') }},</p>
            <p>{{ env('APP_NAME') }}</p>
        </div>
    </body>
</html>
