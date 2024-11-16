@extends('layouts.app')
@section('page-title')
    {{ __('Referral Program') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Referral Program') }}</li>
@endsection

@php

    use App\Models\Utility;
    $setting = Utility::settings();
    $payment_settings = App\Models\Utility::payment_settings();
    $currency_symbol = !isset($payment_settings['currency_symbol']) ? '$' : $payment_settings['currency_symbol']
@endphp

@push('custom-script')
    <script>
        $(document).ready(function() {
            $(".list-group-item").first().addClass('active');

            $(".content-section").hide();

            var activeTabId = $(".list-group-item.active").attr('href');
            $(activeTabId).show();

            $(".list-group-item").on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                $(".list-group-item").removeClass('active');
                $(this).addClass('active');
                $(".content-section").hide();
                $(target).show();
            });
        });

        $('.cp_link').on('click', function() {
            var value = $(this).attr('data-link');
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(value).select();
            document.execCommand("copy");
            $temp.remove();
            show_toastr('Success', '{{ __('Link Copy on Clipboard') }}', 'success')
        });
    </script>
@endpush


@section('content')
    <style>
        .list-group-item.active {
            border: none !important;
        }
    </style>

    <div class="row ">
        <!-- [ sample-page ] start -->
        <div class="col-sm-12">
            <div class="row g-0">
                <div class="col-xl-3 border-end border-bottom">
                    <div class="card shadow-none bg-transparent sticky-top" style="top:70px">
                        <div class="list-group list-group-flush rounded-0" id="useradd-sidenav">
                            <a href="#useradd-1"
                                class="list-group-item list-group-item-action  border-0">{{ __('GuideLine') }}
                                <div class="float-end dark"><i class="ti ti-chevron-right"></i></div>
                            </a>
                            <a href="#useradd-2"
                                class="list-group-item list-group-item-action  border-0">{{ __('Referral Transation') }}
                                <div class="float-end"><i class="ti ti-chevron-right"></i></div>
                            </a>
                            <a href="#useradd-3"
                                class="list-group-item list-group-item-action  border-0">{{ __('Payout') }}
                                <div class="float-end dark"><i class="ti ti-chevron-right"></i></div>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-xl-9" data-bs-spy="scroll" data-bs-target="#useradd-sidenav" data-bs-offset="0"
                    tabindex="0">

                    <div class="card shadow-none rounded-0 border-bottom content-section" id="useradd-1">
                        <div class="card-header">
                            <h5>{{ __('GuideLine') }}</h5>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <h4>{{ __('Refer ' . Auth::user()->name . ' and earn ' . $currency_symbol . $setting['minimum_threshold_amount'] . ' per paid signup!') }}
                                    </h4>
                                    <div class="announcement p-2">
                                        <p class="mb-0">{!! !isset($setting['guidelines']) || is_null($setting['guidelines']) ? '' : $setting['guidelines'] !!}</p>
                                    </div>
                                </div>

                                <div class="col-md-6 form-group text-center">
                                    </br>
                                    <h5>{{ __('Share Your Link') }}</h5>
                                    <div class="row">
                                        <div class="col-12">
                                            <p id="copyText" class="m-0 bg-light-primary">
                                                @if (Auth::user()->create_refercode != '')
                                                    <a href="#!" class="btn btn-light-primary btn-sm w-100 cp_link"
                                                        data-link="{{ route('register', ['ref' => \Auth::user()->create_refercode]) }}"
                                                        data-bs-toggle="tooltip" data-bs-placement="top" title=""
                                                        data-bs-original-title="Click to copy business link">
                                                        {{ route('register', ['ref' => \Auth::user()->create_refercode]) }}<svg
                                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                            class="feather feather-copy ms-1">
                                                            <rect x="9" y="9" width="13" height="13" rx="2"
                                                                ry="2"></rect>
                                                            <path
                                                                d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1">
                                                            </path>
                                                        </svg></a>
                                                @endif
                                            </p>
                                            @if ($setting['referral_status'] != 'on')
                                                <small
                                                    class="text-danger">{{ __('Note : super admin has disabled the referral program.') }}
                                                </small>
                                            @endif

                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="card shadow-none rounded-0 border-bottom content-section" id="useradd-2">
                        <div class="card-header">
                            <h5>{{ __('Referral Transation') }}</h5>
                        </div>

                        <div class="card-body table-border-style">
                            <div class="table-responsive">
                                <table class="table dataTable-5 data-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('#') }}</th>
                                            <th>{{ __('Company Name') }}</th>
                                            <th>{{ __('Plan Name') }}</th>
                                            <th>{{ __('Plan Price') }}</th>
                                            <th>{{ __('Commisssion(%)') }}</th>
                                            <th>{{ __('Commisssion Amount') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="font-style">
                                        @php
                                            $i = 1;
                                        @endphp
                                        @foreach ($commissioncompany as $com)
                                            <tr>
                                                <td>{{ ucwords($i++) }}</td>
                                                <td>{{ ucwords($com['company_name']) }}</td>
                                                <td>{{ ucwords($com['name']) }}</td>
                                                <td>{{ $payment_settings['currency_symbol'] . ucwords($com['price']) }}
                                                </td>
                                                <td>{{ ucwords($com['commisson']) }}</td>
                                                <td>{{ $payment_settings['currency_symbol'] . ucwords($com['commisson_amount']) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-none rounded-0 border-bottom content-section" id="useradd-3">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <h5>{{ __('Payout') }}</h5>
                                </div>
                                <div class="col-md-6 form-group">
                                    <div class="text-sm-end d-flex all-button-box justify-content-sm-end">
                                        <a href="#" class="btn btn-sm btn-primary" data-ajax-popup="true"
                                            data-size="md" data-title="Send Request"
                                            data-url="{{ route('payout.create') }}" data-toggle="tooltip"
                                            title="{{ __('Send Request') }}"
                                            data-bs-original-title="{{ __('Send Request') }}" data-bs-placement="top"
                                            data-bs-toggle="tooltip">
                                            <i class="fas fa-share"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="card-body">
                            {{ Form::open(['route' => 'referral.settings', 'method' => 'post']) }}
                            @csrf
                            <div class="row">
                                <div class="col border-end border-bottom">
                                    <div class="p-3">
                                        <div class="d-flex justify-content-between mb-3">
                                            <div class="theme-avtar bg-primary">
                                                <i class="ti ti-report-money"></i>
                                            </div>
                                            <div>
                                                <p class="text-muted text-sm mb-0">{{ __('Total') }}</p>
                                                <h6 class="mb-0">{{ __('Commission Amount') }}</h6>
                                            </div>
                                        </div>
                                        <h3 class="mb-0">{{ $currency_symbol . $commissionTotal }}
                                        </h3>
                                    </div>
                                </div>
                                <div class="col border-end border-bottom">
                                    <div class="p-3">
                                        <div class="d-flex justify-content-between mb-3">
                                            <div class="theme-avtar bg-primary">
                                                <i class="ti ti-report-money"></i>
                                            </div>
                                            <div>
                                                <p class="text-muted text-sm mb-0">{{ __('Paid') }}</p>
                                                <h6 class="mb-0">{{ __('Commission Amount') }}</h6>
                                            </div>
                                        </div>
                                        <h3 class="mb-0">{{ $currency_symbol . $paidTotal }}</h3>
                                    </div>
                                </div>
                            </div>
                            {{ Form::close() }}
                        </div>


                        <div class="card-header">
                            <h5>{{ __('Payout History') }}</h5>
                        </div>

                        <div class="card-body table-border-style">
                            <div class="table-responsive">
                                <table class="table dataTable-5 data-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('#') }}</th>
                                            <th>{{ __('Company Name') }}</th>
                                            <th>{{ __('Request Date') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Requested Amount') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="font-style">
                                        @php
                                            $i = 1;
                                        @endphp
                                        @foreach ($payout as $pay)
                                            <tr>
                                                <td>{{ ucwords($i++) }}</td>
                                                <td>{{ ucwords($pay['name']) }}</td>
                                                <td>{{ ucwords($pay['created_at']->format('Y-m-d')) }}</td>
                                                <td>{{ ucwords($pay['status']) }}</td>
                                                <td>{{ $payment_settings['currency_symbol'] . ucwords($pay['payout_amount']) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>


                    </div>
                </div>


            </div>
        </div>
    </div>
@endsection
