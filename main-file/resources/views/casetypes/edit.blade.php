{{ Form::open(['route' => ['casetype.update',$casetype->id], 'method' => 'post', 'enctype' => 'multipart/form-data']) }}
@method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="form-group col-md-12">
                {!! Form::label('', __('Name'), ['class' => 'form-label']) !!}
                {!! Form::text('name', $casetype->name, ['class' => 'form-control']) !!}
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{__('Cancel')}}" class="btn btn-secondary btn-light" data-bs-dismiss="modal">
        <input type="submit" value="{{__('Update')}}" class="btn btn-primary ms-2">
    </div>
{{Form::close()}}

<script>

</script>
