@extends('layouts.app')
@section('page-title')
    {{ __('Manage Plans') }}
@endsection
@section('action-button')
    <div>
        @can('create plan')
            @if (count($payment_setting) > 0)
                <div class="float-end">
                    <a href="#" class="btn btn-sm btn-primary btn-icon" data-url="{{ route('plans.create') }}" data-size="lg"
                        data-ajax-popup="true" data-title="{{ __('Create Plan') }}" title="{{ __('Create') }}"
                        data-bs-toggle="tooltip" data-bs-placement="top">
                        <i class="ti ti-plus"></i>
                    </a>
                </div>
            @endif
        @endcan
    </div>
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">{{ __('Plan') }}</li>
@endsection
@php
    $user = Auth::user();
    $settings = App\Models\Utility::payment_settings();
@endphp
@section('content')
    @can('create plan')
        <div class="row g-o p-0">
            <div class="col-12">
                @if (count($payment_setting) == 0)
                    <div class="alert alert-warning"><i class="fe fe-info"></i>
                        {{ __('Please set payment api key & secret key for add new plan') }}</div>
                @endif
            </div>
        </div>
    @endcan

    <div class="row g-0 p-0">
        <div class="col-12">
            <div class="row">
                @foreach ($plans as $plan)
                    <div class="col-xxl-3 col-lg-6 col-md-6 col-sm-6 plan_card mb-0 border-bottom border-end">
                        <div class="card shadow-none  price-card price-1 rounded-0">
                            <div class="card-body">
                                <span class="price-badge bg-primary">{{ $plan->name }}</span>

                                <div class="d-flex flex-row-reverse m-0 p-0 ">
                                    @can('edit plan')
                                        @if ($plan->id != 1)
                                            {!! Form::open([
                                                'method' => 'DELETE',
                                                'route' => ['plans.destroy', $plan->id],
                                                'id' => 'delete-form-' . $plan->id,
                                            ]) !!}
                                            <div class="action-btn bg-light-secondary ms-2">
                                                <a href="#"
                                                    class="mx-3 btn btn-sm d-inline-flex align-items-center bs-pass-para"
                                                    data-id="{{ $plan['id'] }}" data-confirm="{{ __('Are You Sure?') }}"
                                                    data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                    data-confirm-yes="delete-form-{{ $plan->id }}"
                                                    title="{{ __('Delete') }}" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" data-title="{{ __('Delete Plan') }}">
                                                    <i class="ti ti-trash"></i>
                                                </a>
                                            </div>
                                            {!! Form::close() !!}
                                        @endif


                                        <div class="action-btn bg-light-secondary ms-2">
                                            <a href="#" class="mx-3 btn btn-sm d-inline-flex align-items-center"
                                                title="{{ __('Edit') }}" data-bs-toggle="tooltip" data-bs-placement="top"
                                                data-ajax-popup="true" data-size="lg" data-title="{{ __('Edit Plan') }}"
                                                data-url="{{ route('plans.edit', $plan->id) }}" data-bs-toggle="tooltip"
                                                data-bs-placement="top"><i class="ti ti-edit"></i></a>
                                        </div>
                                    @endcan
                                    @if (\Auth::user()->type == 'company' && \Auth::user()->plan == $plan->id)
                                        <span class="d-flex align-items-center ms-2">
                                            <i class="f-10 lh-1 fas fa-circle text-success"></i>
                                            <span class="ms-2">{{ __('Active') }}</span>
                                        </span>
                                    @endif
                                </div>


                                <span class="mb-4 f-w-500 p-price">
                                    {{ isset($settings['currency_symbol']) ? $settings['currency_symbol'] : '$' }}
                                    {{ number_format($plan->price) }} <small class="text-sm">  /  {{ $plan->duration }}</small>
                                </span>
                                <p class="mb-0">
                                </p>
                                <p class="mb-0">
                                    {{ $plan->description }}
                                </p>

                                @if ($plan->trial == 1)
                                    <p class="mb-0">
                                        {{ __('Free Trial Days: '. $plan->trial_days) }}
                                    </p>
                                @endif

                                <ul class="list-unstyled my-4">
                                    <li>
                                        <span class="theme-avtar">
                                            <i class="text-primary ti ti-circle-plus"></i></span>
                                        {{ $plan->max_users < 0 ? __('Unlimited') : $plan->max_users }}
                                        {{ __('Users') }}
                                    </li>
                                    <li>
                                        <span class="theme-avtar">
                                            <i class="text-primary ti ti-circle-plus"></i></span>
                                        {{ $plan->max_advocates < 0 ? __('Unlimited') : $plan->max_advocates }}
                                        {{ __('Advocates') }}
                                    </li>
                                    <li>
                                        <span class="theme-avtar">
                                            <i class="text-primary ti ti-circle-plus"></i></span>
                                        {{ $plan->enable_chatgpt == 'on' ? __('Enable Chat GPT') : __('Disable Chat GPT') }}
                                    </li>
                                </ul>


                                <div class="row d-flex justify-content-between">
                                    @can('buy plan')
                                        @if ($plan->id != \Auth::user()->plan && $plan->price != 0)
                                            @if ($plan->trial == 1 && empty(\Auth::user()->trial_expire_date))
                                                <div class="col-5">
                                                    <div class="d-grid text-center">
                                                        <a href="{{ route('plan.trial', \Illuminate\Support\Facades\Crypt::encrypt($plan->id)) }}"
                                                            class="btn btn-primary btn-sm d-flex justify-content-center align-items-center"
                                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                                            title="{{ __('Free Trial') }}">{{ __('Free Trial') }}
                                                            <i class="fas fa-arrow-right m-1"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            @endif

                                            <div
                                                class="{{ $plan->trial == 1 && !empty(\Auth::user()->trial_expire_date) ? 'col-8' : 'col-5' }}">
                                                <div class="d-grid text-center">
                                                    <a href="{{ route('payment', \Illuminate\Support\Facades\Crypt::encrypt($plan->id)) }}"
                                                        class="btn btn-primary btn-sm d-flex justify-content-center align-items-center"
                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                        title="{{ __('Subscribe') }}">{{ __('Subscribe') }}
                                                        <i class="fas fa-arrow-right m-1"></i></a>
                                                </div>
                                            </div>
                                        @elseif($plan->price <= 0)
                                        @endif
                                        @endcan @if ($plan->id != 1 && \Auth::user()->plan != $plan->id && \Auth::user()->type == 'company')
                                            <div class="col-2">
                                                @if (\Auth::user()->requested_plan != $plan->id)
                                                    <a href="{{ route('send.request', [\Illuminate\Support\Facades\Crypt::encrypt($plan->id)]) }}"
                                                        class="btn btn-primary btn-icon btn-sm"
                                                        data-title="{{ __('Send Request') }}" data-bs-toggle="tooltip"
                                                        data-bs-placement="top" title="{{ __('Send Request') }}">
                                                        <span class="btn-inner--icon"><i class="fas fa-share"></i></span>
                                                    </a>
                                                @else
                                                    <a href="{{ route('request.cancel', \Auth::user()->id) }}"
                                                        class="btn btn-danger btn-icon btn-sm"
                                                        data-title="{{ __('Cancle Request') }}" data-bs-toggle="tooltip"
                                                        data-bs-placement="top" title="{{ __('Cancle Request') }}">
                                                        <span class="btn-inner--icon"><i class="fas fa-times"></i></span>
                                                    </a>
                                                @endif
                                            </div>
                                        @endif

                                        @if (\Auth::user()->type == 'company' && \Auth::user()->plan == $plan->id)
                                            @if (empty(\Auth::user()->plan_expire_date) && empty(Auth::user()->trial_expire_date))
                                                <p class="mb-0">{{ __('Lifetime') }}</p>
                                            @elseif (\Auth::user()->plan_expire_date > \Auth::user()->trial_expire_date)
                                                <p class="mb-0">
                                                    {{ __('Plan Expires on ') }}
                                                    {{ date('d M Y', strtotime(\Auth::user()->plan_expire_date)) }}
                                                </p>

                                            @else
                                                <p class="mb-0">
                                                    {{ __('Trial Expires on ') }}
                                                    {{ !empty(\Auth::user()->trial_expire_date) ? date('d M Y', strtotime(\Auth::user()->trial_expire_date)) : date('Y-m-d') }}
                                                </p>
                                            @endif
                                        @endif

                                        @if (Auth::user()->type == 'super admin' && $plan->id != 1)
                                            <div class="form-switch custom-switch-v1 float-end">
                                                <input type="checkbox" data-id="{{ $plan->id }}"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    title="{{ __('Enable/Disable') }}" class="form-check-input input-primary"
                                                    {{ $plan->status == 1 ? 'checked' : '' }}>
                                            </div>
                                        @endif

                                    </div>

                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endsection

    @push('custom-script')
        <script>
            $(document).on('change', '#trial', function() {
                if ($(this).is(':checked')) {
                    $('.plan_div').removeClass('d-none');
                    $('#trial').attr("required", true);

                } else {
                    $('.plan_div').addClass('d-none');
                    $('#trial').removeAttr("required");
                }
            });

            $('.input-primary').on('change', function() {
                var planId = $(this).data('id');
                var isChecked = $(this).prop('checked');

                $.ajax({
                    type: 'POST',
                    url: '{{ route('update.plan.status') }}',
                    data: {
                        '_token': '{{ csrf_token() }}',
                        'plan_id': planId
                    },
                    success: function(response) {
                        if(response.success){

                            show_toastr('Success', response.message, 'success')
                        }else{
                            show_toastr('Error', response.message, 'error')

                        }
                    },
                    error: function(error) {

                        if (error.status === 404) {
                            $(this).prop('checked', !isChecked);
                        }
                    }
                });
            });
        </script>
    @endpush
