{{ Form::open(['route' => 'timesheet.store', 'method' => 'post', 'enctype' => 'multipart/form-data']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {!! Form::label('case', __('Case'), ['class' => 'form-label']) !!}
            {{-- {!! Form::select('case', $cases, null, ['class' => 'form-control multi-select']) !!} --}}
            <select class="form-control multi-select" name="case" id="case" placeholder="Select Case">
                <option value="">{{ __('Select Case') }}</option>
                @foreach ($cases as $case)
                    <option value="{{ $case->id }}">{{ $case->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('date', __('Date'), ['class' => 'form-label']) }}
            <input id="timesheet_date" placeholder="DD/MM/YYYY" data-input class="form-control text-center" name="date"
                required />
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('particulars', __('Particulars'), ['class' => 'form-label']) !!}
            {!! Form::text('particulars', null, ['class' => 'form-control']) !!}
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('time', __('Time Spent (in Hours)'), ['class' => 'form-label']) }}
            {{ Form::time('time', null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => 'Select time']) }}
        </div>
        <div class="form-group col-md-12">
            {!! Form::label('member', __('Advocate'), ['class' => 'form-label ']) !!}
            <div class="form-group" id="advocate_div">
                {{-- {!! Form::select('member',null, null, ['class' => 'form-control multi-select','id'=>'member']) !!} --}}
                <select class="form-control multi-select" name="member" id="member" placeholder="Select member">
                </select>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary ms-2">
</div>
{{ Form::close() }}
