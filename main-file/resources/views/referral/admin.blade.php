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

        $(document).ready(function() {
            toggleDiv($("#referral_status").prop('checked'));
            $("#referral_status").change(function() {
                var isChecked = $(this).prop('checked');
                toggleDiv(isChecked);
            });
        });

        function toggleDiv(isChecked) {
            if (isChecked) {
                $("#targetDiv").removeClass('disabled').prop('disabled', false);
            } else {
                $("#targetDiv").addClass('disabled').prop('disabled', true);
            }
        }
    </script>
@endpush


@section('content')
    <style>
        .list-group-item.active {
            border: none !important;
        }

        .disabled {
            pointer-events: none;
            opacity: 0.5;
        }
        .switch{
            width: 85px !important;
            height: 40px !important;
        }
    </style>

    <div class="row ">
        <!-- [ sample-page ] start -->
        <div class="col-sm-12">
            <div class="row g-0">
                <div class="col-xl-3 border-end border-bottom">
                    <div class="card shadow-none bg-transparent sticky-top" style="top:70px">
                        <div class="list-group list-group-flush rounded-0" id="useradd-sidenav">
                            <a href="#useradd-1" class="list-group-item list-group-item-action">{{ __('Transaction') }}
                                <div class="float-end dark"><i class="ti ti-chevron-right"></i></div>
                            </a>
                            <a href="#useradd-2" class="list-group-item list-group-item-action">{{ __('Payout Request') }}
                                <div class="float-end dark"><i class="ti ti-chevron-right"></i></div>
                            </a>
                            <a href="#useradd-3" class="list-group-item list-group-item-action">{{ __('Settings') }}
                                <div class="float-end "><i class="ti ti-chevron-right"></i></div>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-xl-9" data-bs-spy="scroll" data-bs-target="#useradd-sidenav" data-bs-offset="0"
                    tabindex="0">
                    <div class="card shadow-none rounded-0 border-bottom content-section" id="useradd-1">
                        <div class="card-header">
                            <h5>{{ __('Transaction') }}</h5>
                        </div>

                        <div class="card-body table-border-style">
                            <div class="table-responsive">
                                <table class="table dataTable-5 data-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('#') }}</th>
                                            <th>{{ __('Company Name') }}</th>
                                            <th>{{ __('Referral owner') }}</th>
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
                                            @php
                                                $matchedCName = '';
                                                foreach ($company as $comp) {
                                                    if ($comp['id'] == $com['use_refercode']) {
                                                        $matchedCName = ucwords($comp['name']);
                                                        break;
                                                    }
                                                }
                                            @endphp
                                            <tr>
                                                <td>{{ ucwords($i++) }}</td>
                                                <td>{{ ucwords($com['company_name']) }}</td>
                                                <td>{{ ucwords($matchedCName) }}</td>
                                                <td>{{ ucwords($com['name']) }}</td>
                                                <td>{{ $currency_symbol.ucwords($com['price']) }}</td>
                                                <td>{{ ucwords($com['commisson']) }}</td>
                                                <td>{{ $currency_symbol.ucwords($com['commisson_amount']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-none rounded-0 border-bottom content-section" id="useradd-2">
                        <div class="card-header">
                            <h5>{{ __('Payout Request') }}</h5>
                        </div>

                        <div class="card-body table-border-style">
                            <div class="table-responsive">
                                <table class="table dataTable-5 data-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('#') }}</th>
                                            <th>{{ __('Company Name') }}</th>
                                            <th>{{ __('Requsted Date') }}</th>
                                            <th>{{ __('Requsted Amount') }}</th>
                                            <th class="text-center">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="font-style">
                                        @php
                                            $i = 1;
                                        @endphp
                                        @foreach ($payout as $pay)
                                            <tr>
                                                <td>{{ ucwords($i++) }}</td>
                                                <td>{{ ucwords($pay['company_name']) }}</td>
                                                <td>{{ ucwords($pay['created_at']->format('Y-m-d')) }}</td>
                                                <td>{{ ucwords($currency_symbol.$pay['payout_amount']) }}</td>
                                                <td class="Action text-center">
                                                    <div>
                                                        <a href="{{ route('payout.update', ['id' => $pay['id'], 'status' => '1']) }}"
                                                            title="{{ __('Accept') }}" data-bs-toggle="tooltip"
                                                            class="action-btn bg-light-secondary ms-2 text-dark">
                                                            <i class="ti ti-check"></i>
                                                        </a>
                                                        <a href="{{ route('payout.update', ['id' => $pay['id'], 'status' => '0']) }}"
                                                            title="{{ __('Delete') }}" data-bs-toggle="tooltip"
                                                            class="action-btn bg-light-secondary ms-2 text-dark">
                                                            <i class="ti ti-x"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-none rounded-0 border-bottom content-section" id="useradd-3">
                        {{ Form::open(['route' => 'referral.settings', 'method' => 'post']) }}
                        @csrf
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col-6">
                                    <h5 class="mb-2">{{ __('Settings') }}</h5>
                                </div>
                                <div class="col-6 switch-width d-flex justify-content-sm-end">
                                    <div class="form-group mb-0">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" data-toggle="switchbutton" data-onstyle="primary"
                                                class="" name="referral_status" id="referral_status"
                                                {{ $setting['referral_status'] == 'on' ? 'checked="checked"' : '' }}>
                                            <label class="custom-control-label" for="referral_status"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body" id="targetDiv">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="col-form-label">{{ __('Commission Percentage(%)') }} </label>
                                    <input type="text" name="commission_percentage" class="form-control"
                                        id="commission_percentage"
                                        value="{{ !isset($setting['commission_percentage']) || is_null($setting['commission_percentage']) ? '' : $setting['commission_percentage'] }}"
                                        required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="currency_symbol"
                                        class="col-form-label">{{ __('Minimum Threshold Amount') }} </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="form-control" id="currency-addon">{{$currency_symbol}}</span>
                                        </div>
                                        <input type="text" name="minimum_threshold_amount" class="form-control"
                                            id="minimum_threshold_amount"
                                            value="{{ !isset($setting['minimum_threshold_amount']) || is_null($setting['minimum_threshold_amount']) ? '' : $setting['minimum_threshold_amount'] }}"
                                            required>
                                    </div>
                                </div>
                                <div class="form-group col-12">
                                    {{ Form::label('content', __('GuideLines'), ['class' => 'col-form-label text-dark']) }}
                                    {{ Form::textarea('guidelines', !isset($setting['guidelines']) || is_null($setting['guidelines']) ? '' : $setting['guidelines'], ['class' => 'summernote form-control']) }}
                                    @error('guidelines')
                                        <span class="invalid-guidelines" role="alert">
                                            <strong class="text-danger">{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="card-footer pb-0">
                            <div class="row">
                                <div class="form-group col-md-6 col-6">

                                </div>
                                <div class="form-group col-md-6 d-flex justify-content-sm-end">
                                    <input class="btn btn-primary" type="submit" value="{{ __('Save Changes') }}">
                                </div>
                            </div>
                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('custom-script')
    <script src="{{ asset('css/summernote/summernote-bs4.js') }}"></script>

    <script>
        $('.summernote').summernote({
            dialogsInBody: !0,
            minHeight: 250,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough']],
                ['list', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'unlink']],
            ]
        });
    </script>
@endpush
