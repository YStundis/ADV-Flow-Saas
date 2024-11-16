@extends('layouts.app')

@section('page-title', __('Timesheet'))

@section('action-button')
    @can('create timesheet')
        <div class="text-sm-end d-flex all-button-box justify-content-sm-end">
            <a href="{{ route('timesheets.export') }}" class="btn btn-sm btn-primary mx-1" data-bs-toggle="tooltip"
                data-title=" {{ __('Export') }}" title="{{ __('Export') }}">
                <i class="ti ti-file-export"></i>
            </a>
            <a href="#" class="btn btn-sm btn-primary mx-1" data-ajax-popup="true" data-size="md" data-title="Add Time Entry"
                data-url="{{ route('timesheet.create') }}" data-toggle="tooltip" title="{{ __('Create New timesheet') }}"
                data-bs-original-title="{{ __('Create New timesheet') }}" data-bs-placement="top" data-bs-toggle="tooltip">
                <i class="ti ti-plus"></i>
            </a>
        </div>
    @endcan
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Timesheet') }}</li>
@endsection

@section('content')
    <div class="row p-0">
        <div class="col-xl-12">
            <div class="card shadow-none">
                <div class="card-body table-border-style">

                    <div class="table-responsive">
                        <table class="table dataTable data-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Case') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Particulars') }}</th>
                                    <th>{{ __('Time Spent (in Hours)') }}</th>
                                    <th>{{ __('Team Member') }}</th>
                                    <th width="100px">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($timesheets as $timesheet)
                                    <tr>
                                        <td><a href="#" class="btn btn-sm"
                                                data-url="{{ route('timesheet.show', $timesheet->id) }}" data-size="md"
                                                data-ajax-popup="true" data-title="{{ __(' View Timesheet') }}">
                                                {{ App\Models\Cases::getCasesById($timesheet->case) }}
                                            </a></td>
                                        <td>{{ $timesheet->date }}</td>
                                        <td>{{ $timesheet->particulars }}</td>
                                        <td>{{ $timesheet->time }}</td>
                                        <td>{{ App\Models\User::getTeams($timesheet->member) }}</td>
                                        <td>
                                            @can('view timesheet')
                                                <div class="action-btn bg-light-secondary ms-2">
                                                    <a href="#" class="mx-3 btn btn-sm d-inline-flex align-items-center "
                                                        data-url="{{ route('timesheet.show', $timesheet->id) }}" data-size="md"
                                                        data-ajax-popup="true" data-title="{{ __(' View Timesheet') }}"
                                                        title="{{ __('View Timesheet') }}" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"><i class="ti ti-eye "></i></a>
                                                </div>
                                            @endcan
                                            @can('edit timesheet')
                                                <div class="action-btn bg-light-secondary ms-2">
                                                    <a href="#" class="mx-3 btn btn-sm d-inline-flex align-items-center "
                                                        data-url="{{ route('timesheet.edit', $timesheet->id) }}" data-size="md"
                                                        data-ajax-popup="true" data-title="{{ __('Edit Timesheet') }}"
                                                        title="{{ __('Edit Timesheet') }}" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"><i class="ti ti-edit "></i>
                                                    </a>
                                                </div>
                                            @endcan
                                            @can('delete timesheet')
                                                <div class="action-btn bg-light-secondary ms-2">
                                                    <a href="#"
                                                        class="mx-3 btn btn-sm d-inline-flex align-items-center bs-pass-para"
                                                        data-confirm="{{ __('Are You Sure?') }}"
                                                        data-text="{{ __('This action can not be undone. Do you want to continue?') }}"
                                                        data-confirm-yes="delete-form-{{ $timesheet->id }}"
                                                        title="{{ __('Delete') }}" data-bs-toggle="tooltip"
                                                        data-bs-placement="top">
                                                        <i class="ti ti-trash"></i>
                                                    </a>
                                                </div>
                                            @endcan
                                            {!! Form::open([
                                                'method' => 'DELETE',
                                                'route' => ['timesheet.destroy', $timesheet->id],
                                                'id' => 'delete-form-' . $timesheet->id,
                                            ]) !!}
                                            {!! Form::close() !!}
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
@endsection
@push('custom-script')
    <script>
        $(document).ready(function() {
            var case_id = $('#case').val();
            getAdvocte(case_id);
        });
        $(document).on('change', 'select[name=case]', function() {

            var case_id = $(this).val();
            getAdvocte(case_id);
        });

        function getAdvocte(case_id) {
            if (case_id) {

                $.ajax({
                    url: "{{ route('get.advocate') }}",
                    type: "POST",
                    data: {
                        case_id: case_id,
                        _token: "{{ csrf_token() }}"
                    },
                    dataType: "json",
                    success: function(result) {
                        // console.log(result.data);

                        $('#member').empty();

                        $("#advocate_div").html('');
                        $('#advocate_div').append(
                            '<select class="form-control multi-select" name="member" id="member"></select>'
                        );

                        // $('#member').append(
                        //     '<option value="">{{ __('Select Assign to') }}</option>');

                        $.each(result.data, function(key, value) {
                            // console.log(key, value);
                            $('#member').append('<option value="' + key + '">' +
                                value + '</option>');
                        });

                        var multipleCancelButton = new Choices('#member', {
                            removeItemButton: true,
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                        $("#member").append(
                            '<option value="">{{ __('Error fetching advocates') }}</option>'
                        );
                    }
                });
            }
        }
    </script>
@endpush
