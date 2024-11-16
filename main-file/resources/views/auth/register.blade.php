@extends('layouts.guest')
@section('page-title')
    {{ __('Cadastrar-se') }}
@endsection
@php
    use App\Models\Utility;
    $languages = Utility::languages();
    $logo = Utility::get_file('uploads/logo');
    $settings = Utility::settings();
    $landinpagesettings = \Modules\LandingPage\Entities\LandingPageSetting::settings();
@endphp
@push('custom-scripts')
    @if ($settings['recaptcha_module'] == 'on')
        @if (isset($settings['google_recaptcha_version']) && $settings['google_recaptcha_version'] == 'v2-checkbox')
            {!! NoCaptcha::renderJs() !!}
        @elseif(isset($settings['google_recaptcha_version']) && $settings['google_recaptcha_version'] == 'v3')
            {{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}
            <script src="https://www.google.com/recaptcha/api.js?render={{ $settings['google_recaptcha_key'] }}"></script>
            <script>
                $(document).ready(function() {
                    grecaptcha.ready(function() {
                        grecaptcha.execute('{{ $settings['google_recaptcha_key'] }}', {
                            action: 'submit'
                        }).then(function(token) {
                            $('#g-recaptcha-response').val(token);
                        });
                    });
                });
            </script>
        @endif
    @endif
@endpush

@section('language-bar')
    <div class="lang-dropdown-only-desk">
        <li class="dropdown dash-h-item drp-language">
            <a class="dash-head-link dropdown-toggle btn" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="drp-text"> {{ ucFirst($languages[$lang]) }} </span>
            </a>
            <div class="dropdown-menu dash-h-dropdown dropdown-menu-end">
                @foreach ($languages as $code => $language)
                    <a href="{{ route('register', ['lang' => $code, 'ref' => request('ref')]) }}" tabindex="0"
                        class="dropdown-item {{ $code == $lang ? 'active' : '' }}">
                        <span>{{ ucFirst($language) }}</span>
                    </a>
                @endforeach
            </div>
        </li>
    </div>
@endsection
@section('content')
    <style>
        .g-recaptcha {
            filter: invert(1) hue-rotate(180deg) !important;
        }

        .grecaptcha-badge {
            z-index: 99999999 !important;
        }
    </style>
    <div class="card-body">
        <div>
            <h2 class="mb-3 f-w-600">{{ __('Cadastrar-se') }}</h2>
        </div>
        <form method="POST" action="{{ route('register') }}">
            @if (session('status'))
                <div class="mb-4 font-medium text-lg text-green-600 text-danger">
                    {{ __('Email SMTP settings does not configured so please contact to your site admin.') }}
                </div>
            @endif
            @if (session('Invalidererral'))
                <div class="mb-4 font-medium text-lg text-green-600 text-danger">
                    {{ __('Invalide reffral linke.') }}
                </div>
            @endif
            @csrf
            <input type="hidden" name="refUserId" value="{{ $refUserId }}">
            <div class="custom-login-form">
                <div class="form-group mb-3">
                    <label for="name" class="form-label">{{ __('Name') }}</label>
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                        name="name" value="{{ old('name') }}" required autocomplete="name" autofocus
                        placeholder="{{ __('Name') }}">
                    @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="form-group mb-3">
                    <label for="email" class="form-label">{{ __('Email') }}</label>
                    <input class="form-control @error('email') is-invalid @enderror" id="email" type="email"
                        name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                        placeholder="{{ __('Email') }}">
                    @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <div class="invalid-feedback">
                        {{ __('Please fill in your email') }}
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label for="password" class="form-label">{{ __('Password') }}</label>
                    <input id="password" type="password" data-indicator="pwindicator"
                        class="form-control pwstrength @error('password') is-invalid @enderror" name="password" required
                        autocomplete="new-password" placeholder="{{ __('Password') }}">
                    @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <div id="pwindicator" class="pwindicator">
                        <div class="bar"></div>
                        <div class="label"></div>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label for="password_confirmation" class="form-label">{{ __('Password Confirmation') }}</label>
                    <input id="password_confirmation" type="password" data-indicator="password_confirmation"
                        class="form-control pwstrength @error('password_confirmation') is-invalid @enderror"
                        name="password_confirmation" required autocomplete="new-password"
                        placeholder="{{ __('Confirm Password') }}">
                    @error('password_confirmation')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <div id="password_confirmation" class="pwindicator">
                        <div class="bar"></div>
                        <div class="label"></div>
                    </div>
                </div>
                <div class="form-check custom-checkbox">
                    <input type="checkbox" class="form-check-input" id="termsCheckbox" name="terms" required>
                    <label class="form-check-label text-sm" for="termsCheckbox">
                        @if (is_array(json_decode($landinpagesettings['menubar_page'])) ||
                                is_object(json_decode($landinpagesettings['menubar_page'])))
                            @php
                                $pages = json_decode($landinpagesettings['menubar_page']);
                                $termsAndConditions = null;
                                $privacyPolicy = null;

                                foreach ($pages as $page) {
                                    if ($page->page_slug == 'terms_and_conditions' && isset($page->template_name)) {
                                        $termsAndConditions = $page;
                                    }
                                    if ($page->page_slug == 'privacy_policy' && isset($page->template_name)) {
                                        $privacyPolicy = $page;
                                    }
                                }
                            @endphp
                            @if ($termsAndConditions || $privacyPolicy)
                                {{ __('Eu concordo com o ') }}
                            @endif
                            @if ($termsAndConditions)
                                <a href="{{ $termsAndConditions->template_name == 'page_content' ? route('custom.page', $termsAndConditions->page_slug) : $termsAndConditions->page_url }}"
                                    target="_blank">{{ $termsAndConditions->menubar_page_name }}</a>
                            @endif
                            @if ($termsAndConditions && $privacyPolicy)
                                {{ __('e ') }}
                            @endif
                            @if ($privacyPolicy)
                                <a href="{{ $privacyPolicy->template_name == 'page_content' ? route('custom.page', $privacyPolicy->page_slug) : $privacyPolicy->page_url }}"
                                    target="_blank">{{ $privacyPolicy->menubar_page_name }}</a>
                            @endif
                        @endif
                    </label>
                </div>
                @if ($settings['recaptcha_module'] == 'on')
                    @if (isset($settings['google_recaptcha_version']) && $settings['google_recaptcha_version'] == 'v2-checkbox')
                        <div class="form-group mb-3">
                            {!! NoCaptcha::display() !!}
                            @error('g-recaptcha-response')
                                <span class="small text-danger" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    @else
                        <div class="form-group col-lg-12 col-md-12 mt-3">
                            <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response"
                                class="form-control">
                            @error('g-recaptcha-response')
                                <span class="error small text-danger" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    @endif
                @endif

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary mt-2">{{ __('Cadastrar-se') }}</button>
                </div>

                <p class="my-4 text-center">{{ __('Already have an account?') }}
                    <a href="{{ route('login', !empty($lang) ? $lang : 'en') }}" class="text-primary">
                        {{ __('Login') }}
                    </a>
                </p>
            </div>
            {{ Form::close() }}
    </div>
@endsection
