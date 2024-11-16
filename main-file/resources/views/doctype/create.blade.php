@php
    $setting = App\Models\Utility::settings();
@endphp
{{ Form::open(['route' => 'doctype.store', 'method' => 'post']) }}
<div class="modal-body">
    <div class="row">
        @if (!empty($setting['chatgpt_key']))
            <div>
                <a href="#" data-size="md" data-ajax-popup-over="true"
                    data-url="{{ route('generate', ['document_type']) }}" data-bs-toggle="tooltip" data-bs-placement="top"
                    title="{{ __('Generate') }}" data-title="{{ __('Generate content with AI') }}"
                    class="btn btn-primary btn-sm float-end">
                    <i class="fas fa-robot"></i>
                    {{ __('Generate with AI') }}
                </a>
            </div>
        @endif
        <div class="form-group col-md-12">
            {!! Form::label('name', __('Type'), ['class' => 'form-label']) !!}
            {!! Form::text('name', null, ['class' => 'form-control']) !!}
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('description', __('Description'), ['class' => 'form-label']) !!}
            {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => '3', 'maxlength' => '150']) !!}
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary ms-2">
</div>
{{ Form::close() }}
