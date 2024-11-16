{{ Form::open(['route' => 'users.store', 'method' => 'post']) }}
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-6">
                {!! Form::label('name', __('Name'), ['class' => 'form-label']) !!}
                {!! Form::text('name', null, ['class' => 'form-control','placeholder'=>__('Enter User Name')]) !!}

            </div>
            <div class="form-group col-md-6">
                {{ Form::label('Email', __('Email'), ['class' => 'form-label']) }}
                {!! Form::text('email', null, ['class' => 'form-control','placeholder'=>__('Enter User Email')]) !!}

            </div>

            @if (Auth::user()->type == 'company')
                <div class="form-group col-md-6">
                    {{ Form::label('role', __('Role'), ['class' => 'form-label']) }}
                    {!! Form::select('role', $roles, null, ['class' => 'form-control multi-select']) !!}
                </div>
            @endif

            <div class="col-md-6 mb-3 form-group mt-4">
                <label for="password_switch">{{ __('Login is enable') }}</label>
                <div class="form-check form-switch custom-switch-v1 float-end">
                    <input type="checkbox" name="password_switch" class="form-check-input input-primary pointer"
                        value="on" id="password_switch">
                    <label class="form-check-label" for="password_switch"></label>
                </div>
            </div>

            <div class="form-group col-md-6 ps_div d-none">
                {!! Form::label('password', __('Password'), ['class' => 'form-label']) !!}
                {{Form::password('password',array('class'=>'form-control','placeholder'=>__('Enter User Password'),'minlength'=>"8"))}}
            </div>

        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{__('Cancel')}}" class="btn btn-secondary btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{__('Create')}}" class="btn btn-primary ms-2">
    </div>
{{Form::close()}}


