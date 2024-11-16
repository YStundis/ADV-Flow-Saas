@php
    use App\Models\Utility;
    $chatgpt_key = Utility::getValByName('chatgpt_key');
    $chatgpt_enable = !empty($chatgpt_key);
@endphp

{{ Form::open(['url' => 'plans', 'enctype' => 'multipart/form-data']) }}
<div class="modal-body">

    @if ($chatgpt_enable)
        <div class="text-end">
            <a href="#" class="btn btn-sm btn-primary" data-size="medium" data-ajax-popup-over="true"
                data-url="{{ route('generate', ['plan']) }}" data-bs-toggle="tooltip" data-bs-placement="top"
                title="{{ __('Generate') }}" data-title="{{ __('Generate Content With AI') }}">
                <i class="fas fa-robot"></i>{{ __(' Generate With AI') }}
            </a>
        </div>
    @endif
    <div class="row">
        <div class="form-group col-md-6">
            {{ Form::label('name', __('Name'), ['class' => 'col-form-label']) }}
            {{ Form::text('name', null, ['class' => 'form-control ', 'placeholder' => __('Enter Plan Name'), 'required' => 'required']) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('price', __('Price'), ['class' => 'col-form-label']) }}
            {{ Form::text('price', null, ['class' => 'form-control', 'placeholder' => __('Enter Plan Price'), 'required' => 'required','step' => '0.01']) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('duration', __('Duration'), ['class' => 'col-form-label']) }}
            {!! Form::select('duration', $arrDuration, null, ['class' => 'form-select', 'required' => 'required']) !!}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('max_users', __('Maximum Users'), ['class' => 'col-form-label']) }}
            {{ Form::number('max_users', null, ['class' => 'form-control', 'placeholder' => __('Enter Maximum Users'), 'required' => 'required']) }}
            <span class="small">{{ __('Note: "-1" for Unlimited') }}</span>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('max_advocates', __('Maximum Advocates'), ['class' => 'col-form-label']) }}
            {{ Form::number('max_advocates', null, ['class' => 'form-control', 'placeholder' => __('Enter Maximum Advocates'), 'required' => 'required']) }}
            <span class="small">{{ __('Note: "-1" for Unlimited') }}</span>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('storage_limit', __('Maximum Storage Limit'), ['class' => 'col-form-label']) }}
            <div class="input-group">
                {{ Form::number('storage_limit', null, ['class' => 'form-control', 'placeholder' => __('Enter Maximum Storage Limit'), 'required' => 'required']) }}
                <div class="input-group-append">
                    <span class="input-group-text" id="basic-addon2">MB</span>
                </div>
            </div>
            <span class="small">{{ __('Note: Upload size (in MB)') }}</span>
        </div>
        <div class="col-md-4 mt-3 plan_price_div">
            <label class="form-check-label" for="enable_chatgpt"></label>
            <div class="form-group">
                <label for="enable_chatgpt" class="form-label">{{ __('Enable Chatgpt') }}</label>
                <div class="form-check form-switch custom-switch-v1 float-end">
                    <input type="checkbox" name="enable_chatgpt" class="form-check-input input-primary " value="on"
                        id="enable_chatgpt">
                    <label class="form-check-label" for="enable_chatgpt"></label>
                </div>
            </div>
        </div>

        <div class="col-md-4 mt-3 plan_price_div">
            <label class="form-check-label" for="trial"></label>
            <div class="form-group">
                <label for="trial" class="form-label">{{ __('Trial is enable(on/off)') }}</label>
                <div class="form-check form-switch custom-switch-v1 float-end">
                    <input type="checkbox" name="trial" class="form-check-input input-primary pointer" value="1"
                        id="trial">
                    <label class="form-check-label" for="trial"></label>
                </div>
            </div>
        </div>

        <div class="col-md-4 d-none plan_div plan_price_div">
            <div class="form-group">
                {{ Form::label('trial_days', __('Trial Days'), ['class' => 'form-label']) }}
                {{ Form::number('trial_days', null, ['class' => 'form-control', 'placeholder' => __('Enter Trial days'), 'step' => '1', 'min' => '1', 'oninput' => 'this.value = Math.max(1, this.value);']) }}
            </div>
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('description', __('Description'), ['class' => 'col-form-label']) }}
            {!! Form::textarea('description', null, [
                'class' => 'form-control',
                'placeholder' => __('Enter Plan Description'),
                'rows' => '2',
            ]) !!}
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary ms-2">
</div>
{{ Form::close() }}
