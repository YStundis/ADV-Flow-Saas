@php

    $setting = App\Models\Utility::settings();

@endphp
{{ Form::open(['route' => 'to-do.store', 'method' => 'post', 'enctype' => 'multipart/form-data']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {!! Form::label('title', __('Title'), ['class' => 'form-label']) !!}
            {!! Form::text('title', null, ['rows' => 4, 'class' => 'form-control', 'required' => 'required']) !!}
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('relate_to', __('Relate to (Case(s))'), ['class' => 'form-label']) !!}
            <select class="form-control multi-select" name="relate_to" id="relate_to" placeholder="Select Case">
                <option value="">{{ __('Select Case') }}</option>
                @foreach ($cases as $case)
                    <option value="{{ $case->id }}">{{ $case->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('due_date', __('Assigned Date'), ['class' => 'form-label']) }}
            <input placeholder="DD/MM/YYYY" data-input class="form-control text-center single-date" name="assigned_date"
                required />
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('due_date', __('Due Date'), ['class' => 'form-label']) }}
            <input placeholder="DD/MM/YYYY" data-input class="form-control text-center single-date" name="due_date"
                required />
        </div>

        {{-- <div class="form-group col-md-12">
            {!! Form::label('assign_to', __('Assign To (Advocates/Members)'), ['class' => 'form-label']) !!}
            {!! Form::select('assign_to[]', $teams, null, [
                'class' => 'form-control multi-select',
                'id' => 'choices-multiple1',
                'multiple',
            ]) !!}
        </div> --}}

        <div class="form-group col-md-12">
            {{ Form::label('assign_to', __('Assign To (Advocates/Members)'), ['class' => 'form-label']) }}
            <div class="form-group" id="advocate_div">
                <select class="form-control multi-select" name="assign_to[]" id="assign_to" placeholder="Select Assgin"
                    multiple>
                </select>
            </div>
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('assign_to', __('Priority'), ['class' => 'form-label']) !!}
            <select name="priority" id="priority" class="form-control multi-select">
                @foreach ($priorities as $priority)
                    <option value="{{ $priority }}">{{ $priority }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('description', __('Description'), ['class' => 'form-label']) !!}
            {!! Form::textarea('description', null, ['rows' => 2, 'class' => 'form-control', 'maxlength' => '150']) !!}
        </div>
        @if (isset($setting['is_enabled']) && $setting['is_enabled'] == 'on')
        <div class="form-group col-md-12 row">
            <div class="form-group col-md-8 mt-2">
                <label for="is_check" class="form-check-label">{{ __('Synchronize in Google Calendar') }}</label>
            </div>
            <div class="form-group col-md-4">
                <div class="form-check form-switch pt-2 custom-switch-v1 float-end">
                    <input id="switch-shadow" class="form-check-input" value="1" name="is_check" type="checkbox"
                        id="is_check">
                    <label class="form-check-label" for="switch-shadow"></label>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary ms-2">
</div>
{{ Form::close() }}
